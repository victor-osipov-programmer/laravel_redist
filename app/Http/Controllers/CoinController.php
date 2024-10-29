<?php

namespace App\Http\Controllers;

use App\Http\Requests\BuyCoinRequest;
use App\Http\Requests\BuyToBankCoinRequest;
use App\Http\Requests\SellCoinRequest;
use App\Http\Requests\SellToBankCoinRequest;
use App\Models\Coin;
use App\Http\Requests\StoreCoinRequest;
use App\Jobs\ResetCoinLimitsJob;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CoinController extends Controller
{
    function index(Request $request) {
        $user = $request->user();
        $coins = Coin::with('users')->get();
        return $coins->map(function ($coin) use ($user) {
            $coin_user = $coin->users->find($user->id);

            if (isset($coin_user)) {
                $coin->user_coins = $coin_user->pivot->coins;
            } else {
                $coin->user_coins = 0;
            }
            unset($coin->users);

            return $coin;
        });
    }

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

            ResetCoinLimitsJob::dispatch($coin->id)->delay(now()->addSeconds($coin->one_cycle));
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

        $order = new Order;
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
        $view_order = new Order;
        $view_order->table = 'view_orders';
        
        $sell_orders = $view_order
        ->where('type', 'sell')
        ->where('price_coin', '<=', $buy_order->price_coin)
        ->where('coin_id', $coin->id)
        ->orderBy('price_coin')
        ->orderByDesc('user_donations')
        ->get();
        $received_coins = 0;


        foreach ($sell_orders as $sell_order) {
            try {
                DB::beginTransaction();
                $coins_turnover = min($sell_order->number_coins, $buy_order->number_coins);
                $price_coins = $coins_turnover * $sell_order->price_coin;
                $commission = $price_coins * ($coin->commission / 100);
                $price_coins -= $commission;

                $coin->update([
                    'income' => $coin->icome + $commission
                ]);

                $sell_order->user->update([
                    'balance' => $sell_order->user->balance + $price_coins
                ]);
                $START = $buy_order->user->coins->find($coin->id)->pivot->coins;
                $buy_order->user->coins()->updateExistingPivot($coin->id, [
                    'coins' => $buy_order->user->coins->find($coin->id)->pivot->coins + $coins_turnover
                ]);
                $END = $buy_order->user->coins->find($coin->id)->pivot->coins;
                Log::info("buy_order->user->coins->find(coin->id)->pivot->coins START $START, END $END");

                $sell_order_number_coins = $sell_order->number_coins - $coins_turnover;
                if ($sell_order_number_coins == 0) {
                    $sell_order->table = 'orders';
                    $sell_order->delete();
                } else {
                    $sell_order->update([
                        'number_coins' => $sell_order_number_coins
                    ]);
                }
                
                $buy_order_number_coins = $buy_order->number_coins - $coins_turnover;
                if ($buy_order_number_coins == 0) {
                    $buy_order->table = 'orders';
                    $buy_order->delete();
                } else {
                    $buy_order->update([
                        'number_coins' => $buy_order_number_coins
                    ]);
                }

                

                $received_coins += $coins_turnover;
                DB::commit();

                if ($buy_order_number_coins == 0) {
                    return [
                        'message' => 'Buy order completed',
                        'received_coins' => $received_coins,
                        'sell_orders' => $sell_orders
                    ];
                }
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }

        return response([
            'message' => 'Created buy order',
            'received_coins' => $received_coins,
            'sell_orders' => $sell_orders
        ], 201);
    }

    function sell(SellCoinRequest $request, Coin $coin)
    {
        $data = $request->validated();
        $user = $request->user();
        
        $sell_order = new Order;
        DB::transaction(function () use($data, $user, $coin, &$sell_order) {
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
        $view_order = new Order;
        $view_order->table = 'view_orders';
        
        $buy_orders = $view_order
        ->where('type', 'buy')
        ->where('price_coin', '>=', $sell_order->price_coin)
        ->where('coin_id', $coin->id)
        ->orderByDesc('price_coin')
        ->orderByDesc('user_donations')
        ->get();
        $received_currency = 0;
        $shared_commision = 0;


        foreach ($buy_orders as $buy_order) {
            try {
                DB::beginTransaction();
                $coins_turnover = min($sell_order->number_coins, $buy_order->number_coins);
                $price_coins = $coins_turnover * $buy_order->price_coin;
                $commission = $price_coins * ($coin->commission / 100);
                $price_coins -= $commission;

                $coin->update([
                    'income' => $coin->icome + $commission
                ]);

                $sell_order->user->update([
                    'balance' => $sell_order->user->balance + $price_coins
                ]);
                $buy_order->user->coins()->updateExistingPivot($coin->id, [
                    'coins' => $buy_order->user->coins->find($coin->id)->pivot->coins + $coins_turnover
                ]);

                $sell_order_number_coins = $sell_order->number_coins - $coins_turnover;
                if ($sell_order_number_coins == 0) {
                    $sell_order->table = 'orders';
                    $sell_order->delete();
                } else {
                    $sell_order->update([
                        'number_coins' => $sell_order_number_coins
                    ]);
                }
                
                $buy_order_number_coins = $buy_order->number_coins - $coins_turnover;
                if ($buy_order_number_coins == 0) {
                    $buy_order->table = 'orders';
                    $buy_order->delete();
                } else {
                    $buy_order->update([
                        'number_coins' => $buy_order_number_coins
                    ]);
                }

                
                $shared_commision += $commission;
                $received_currency += $price_coins;
                DB::commit();

                if ($sell_order_number_coins == 0) {
                    return [
                        'message' => 'Sell order completed',
                        'received_currency' => $received_currency,
                        'commission' => $shared_commision,
                        'buy_orders' => $buy_orders
                    ];
                }
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }

        return response([
            'message' => 'Created sell order',
            'received_currency' => $received_currency,
            'commission' => $shared_commision,
            'buy_orders' => $buy_orders
        ], 201);
    }

    function test(Coin $coin) {
        $coins = Coin::all();

        foreach ($coins as $coin) {
            ResetCoinLimitsJob::dispatch($coin->id)->delay(now()->addSeconds($coin->one_cycle));
        }
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
            'message' => 'Success buy coins',
            'spent_currency' => $price_coins
        ];
    }

    function sell_to_bank(SellToBankCoinRequest $request, Coin $coin)
    {
        $data = $request->validated();
        $user = $request->user(); 
        $user_pivot = $user->coins->find($coin->id)->pivot;
        $price_coins = $data['number_coins'] * $coin->price_sale_coin;

        if ($coin['income'] < $coin['expenses']) {
            throw new AccessDeniedHttpException('Bank income should be more than expenses');
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
            'message' => 'Success sell coins',
            'received_currency' => $price_coins
        ];
    }
}
