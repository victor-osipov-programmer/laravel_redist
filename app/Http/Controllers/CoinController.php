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
            'message' => 'Created',
            'data' => $coin
        ], Response::HTTP_CREATED);
    }
    function buy(BuyCoinRequest $request)
    {
        
    }
    function sell(SellCoinRequest $request)
    {
        
    }
    function buy_to_bank(BuyToBankCoinRequest $request)
    {
        
    }
    function sell_to_bank(SellToBankCoinRequest $request)
    {
        
    }
}
