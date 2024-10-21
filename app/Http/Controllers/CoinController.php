<?php

namespace App\Http\Controllers;

use App\Http\Requests\BuyCoinRequest;
use App\Http\Requests\BuyToBankCoinRequest;
use App\Http\Requests\SellCoinRequest;
use App\Http\Requests\SellToBankCoinRequest;
use App\Models\Coin;
use App\Http\Requests\StoreCoinRequest;
use App\Http\Requests\UpdateCoinRequest;
use App\Models\Order;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CoinController extends Controller
{
    function store(StoreCoinRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        if (!isset($data['max_buy_coins_cycle'])) $data['max_buy_coins_cycle'] = round($data['total_coins'] / 100);
        if (!isset($data['max_buy_coins_game'])) $data['max_buy_coins_game'] = round($data['total_coins'] / 10);
        if (!isset($data['max_buy_additional_coins_cycle'])) $data['max_buy_additional_coins_cycle'] = round($data['total_coins'] / 100);
        if (!isset($data['max_buy_additional_coins_game'])) $data['max_buy_additional_coins_game'] = round($data['total_coins'] / 10);
        if (!isset($data['price_buy_additional_coin'])) $data['price_buy_additional_coin'] = $data['price_sale_coin'];
        if (!isset($data['min_number_coins_sale'])) $data['min_number_coins_sale'] = round($data['total_coins'] / 10);

        $data['buy_to_bank_coins'] = $data['total_coins'];
        $data['sale_to_bank_coins'] = $data['total_coins'];
        $data['creator_id'] = $user->id;
        $data['expenses'] = $data['total_coins'] * $data['price_sale_coin'] - $data['total_coins'] * $data['price_buy_coin'];

        $coin = null;
        DB::transaction(function () use($data, $user, &$coin) {
            $coin = Coin::create($data);
            $coin->users()->attach($user->id, [
                'max_buy_coins_cycle' => $data['max_buy_coins_cycle'],
                'max_buy_coins_game' => $data['max_buy_coins_game'],
                'max_buy_additional_coins_cycle' => $data['max_buy_additional_coins_cycle'],
                'max_buy_additional_coins_game' => $data['max_buy_additional_coins_game'],
            ]);
        });
        
        return response([
            'message' => 'Created coin',
            'data' => $coin
        ], 201);
    }

    function buy(BuyCoinRequest $request, Coin $coin)
    {
        $data = $request->validated();
        $user = $request->user();
        $price_coins = $data['number_coins'] * $data['price_coin'];

        if ($user->balance < $price_coins) {
            throw new AccessDeniedHttpException("The price_coins is $price_coins, your balance is $user->balance");
        }

        $order = null;
        DB::transaction(function () use($data, $user, $coin, $price_coins, &$order) {
            $order = Order::create([
                'coin_id' => $coin->id,
                'user_id' => $user->id,
                'type' => 'buy',
                'number_coins' => $data['number_coins'],
                'price_coin' => $data['price_coin'],
            ]);
            $user->update([
                'balance' => $user->balance - $price_coins
            ]);
        });
        
        return $this->execute_buy_order($order, $coin);
    }

    function execute_buy_order(Order $buy_order, $coin) {
        $sell_orders = DB::table('view_orders')
        // ->select('id')
        ->where('type', 'sell')
        ->where('price_coin', '<=', $buy_order->price_coin)
        ->where('coin_id', $coin->id)
        ->orderBy('price_coin')
        ->orderByDesc('user_donations')
        ->get();
        
        $sell_orders = Order::find($sell_orders->map(fn ($order) => $order->id))->reverse();
        $coins_income = 0;


        foreach ($sell_orders as $sell_order) {
            try {
                DB::beginTransaction();
                $coins_turnover = min($sell_order->number_coins, $buy_order->number_coins);
                $price_coins = $coins_turnover * $sell_order->price_coin;
                
                $sell_order->user->update([
                    'balance' => $sell_order->user->balance + $price_coins
                ]);
                $buy_order->user->coins()->updateExistingPivot($coin->id, [
                    'coins' => $buy_order->user->coins->find($coin->id)->coins + $coins_turnover
                ]);

                $sell_order_number_coins = $sell_order->number_coins - $coins_turnover;
                if ($sell_order_number_coins == 0) {
                    $sell_order->delete();
                } else {
                    $sell_order->update([
                        'number_coins' => $sell_order_number_coins
                    ]);
                }
                
                $buy_order_number_coins = $buy_order->number_coins - $coins_turnover;
                if ($buy_order_number_coins == 0) {
                    $buy_order->delete();
                } else {
                    $buy_order->update([
                        'number_coins' => $buy_order_number_coins
                    ]);
                }

                

                $coins_income += $coins_turnover;
                DB::commit();

                if ($sell_order_number_coins == 0) {
                    return [
                        'message' => 'Order was completed successfully',
                        'coins_turnover' => $coins_turnover
                    ];
                }
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }

        return [
            'message' => 'Created buy order',
            'orders' => $sell_orders
        ];
    }

    function sell(SellCoinRequest $request, Coin $coin)
    {
        $data = $request->validated();
        $user = $request->user();
        $price_coins = $data['number_coins'] * $data['price_coin'];

        if ($user->balance < $price_coins) {
            throw new AccessDeniedHttpException("The price_coins is $price_coins, your balance is $user->balance");
        }

        $sell_order = null;
        DB::transaction(function () use($data, $user, $coin, $price_coins, &$sell_order) {
            $sell_order = Order::create([
                'coin_id' => $coin->id,
                'user_id' => $user->id,
                'type' => 'sell',
                'number_coins' => $data['number_coins'],
                'price_coin' => $data['price_coin'],
            ]);
            $user->coins()->updateExistingPivot($coin->id, [
                'coins' => $user->coins->find($coin->id)->pivot->coins - $data['number_coins']
            ]);
        });
        
        return $this->execute_sell_order($sell_order, $coin);

        
    }


    function execute_sell_order(Order $sell_order, $coin) {
        $buy_orders = DB::table('view_orders')
        ->select('id')
        ->where('type', 'buy')
        ->where('price_coin', '>=', $sell_order->price_coin)
        ->where('coin_id', $coin->id)
        ->orderByDesc('price_coin')
        ->orderByDesc('user_donations')
        ->get();
        $buy_orders = Order::find($buy_orders->map(fn ($order) => $order->id));
        $currency_income = 0;


        foreach ($buy_orders as $buy_order) {
            try {
                DB::beginTransaction();
                $coins_turnover = min($sell_order->number_coins, $buy_order->number_coins);
                $price_coins = $coins_turnover * $buy_order->price_coin;
                
                $sell_order->user->update([
                    'balance' => $sell_order->user->balance + $price_coins
                ]);
                $buy_order->user->coins()->updateExistingPivot($coin->id, [
                    'coins' => $buy_order->user->coins->find($coin->id)->coins + $coins_turnover
                ]);

                $sell_order_number_coins = $sell_order->number_coins - $coins_turnover;
                if ($sell_order_number_coins == 0) {
                    $sell_order->delete();
                } else {
                    $sell_order->update([
                        'number_coins' => $sell_order_number_coins
                    ]);
                }
                
                $buy_order_number_coins = $buy_order->number_coins - $coins_turnover;
                if ($buy_order_number_coins == 0) {
                    $buy_order->delete();
                } else {
                    $buy_order->update([
                        'number_coins' => $buy_order_number_coins
                    ]);
                }

                

                $currency_income += $price_coins;
                DB::commit();

                if ($sell_order_number_coins == 0) {
                    return [
                        'message' => 'Order was completed successfully',
                        'currency_income' => $currency_income
                    ];
                }
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }

        return [
            'message' => 'Created sell order'
        ];
    }

    function test(Coin $coin) {
        $sell_order = Order::where('type', 'sell')->orderBy('price_coin')->first();

        return [
            'sell_order' => $sell_order,
            'orders' => $this->execute_sell_order($sell_order, $coin)
        ];
    }

    function buy_to_bank(BuyToBankCoinRequest $request, Coin $coin)
    {
        $data = $request->validated();
        $user = $request->user(); 
        $user_pivot = $user->coins->find($coin->id)->pivot;

        if ($data['additional_coins']) {
            $price_coins = $data['number_coins'] * $coin->price_buy_additional_coin;
            $text_max_buy_coins_cycle = 'max_buy_additional_coins_cycle';
            $text_max_buy_coins_game = 'max_buy_additional_coins_game';
        } else {
            $price_coins = $data['number_coins'] * $coin->price_buy_coin;
            $text_max_buy_coins_cycle = 'max_buy_coins_cycle';
            $text_max_buy_coins_game = 'max_buy_coins_game';
        }

        if ($user->balance < $price_coins) {
            throw new AccessDeniedHttpException("The price_coins is $price_coins, your balance is $user->balance");
        }
        
        DB::transaction(function () use($user, $coin, $data, $user_pivot, $text_max_buy_coins_cycle, $text_max_buy_coins_game, $price_coins) {
            $user->update([
                'balance' => $user->balance - $price_coins
            ]);
            $user->coins()->updateExistingPivot($coin->id, [
                'coins' => $user_pivot->coins + $data['number_coins'],
                $text_max_buy_coins_cycle => $user_pivot->$text_max_buy_coins_cycle - $data['number_coins'],
                $text_max_buy_coins_game => $user_pivot->$text_max_buy_coins_game - $data['number_coins']
            ]);
            $coin->update([
                'buy_to_bank_coins' => $coin->buy_to_bank_coins - $data['number_coins']
            ]);
        });

        return [
            'message' => 'Success buy of coins'
        ];
    }

    function sell_to_bank(SellToBankCoinRequest $request, Coin $coin)
    {
        $data = $request->validated();
        $user = $request->user(); 
        $user_pivot = $user->coins->find($coin->id)->pivot;
        $price_coins = $data['number_coins'] * $coin->price_sale_coin;

        if ($coin['income'] < $coin['expenses']) {
            throw new AccessDeniedHttpException('income should be more than expenses');
        }
        
        DB::transaction(function () use($user, $coin, $data, $user_pivot, $price_coins) {
            $user->coins()->updateExistingPivot($coin->id, [
                'coins' => $user_pivot->coins - $data['number_coins'],
            ]);
            $user->update([
                'balance' => $user->balance + $price_coins
            ]);
            $coin->update([
                'sale_to_bank_coins' => $coin->sale_to_bank_coins - $data['number_coins']
            ]);
        });

        return [
            'message' => 'Success sell of coins'
        ];
    }
}
