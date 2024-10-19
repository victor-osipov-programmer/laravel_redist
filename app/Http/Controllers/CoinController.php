<?php

namespace App\Http\Controllers;

use App\Http\Requests\BuyCoinRequest;
use App\Http\Requests\BuyToBankCoinRequest;
use App\Http\Requests\SellCoinRequest;
use App\Http\Requests\SellToBankCoinRequest;
use App\Models\Coin;
use App\Http\Requests\StoreCoinRequest;
use App\Http\Requests\UpdateCoinRequest;
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
        
    }

    function sell(SellCoinRequest$request, Coin $coin)
    {
        
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
        
    }
}
