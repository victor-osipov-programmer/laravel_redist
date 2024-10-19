<?php

namespace App\Http\Requests;

use App\Models\Coin;
use App\Models\User;
use App\Rules\BuyRestrictionsNumberCoins;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class BuyToBankCoinRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(User $user): array
    {
        $user = $this->user();
        $coin = $this->route('coin');
        $user_pivot = $user->coins->find($coin->id)->pivot;

        return [
            'number_coins' => [
                'required', 
                'integer',
                'min:1',
                function (string $attribute, mixed $value, Closure $fail) use ($user_pivot, $user, $coin) {
                    $user_pivot = $user->coins->find($coin->id)->pivot;

                    if ($this->additional_coins) {
                        if ($this->number_coins > $user_pivot->max_buy_additional_coins_cycle) {
                            $fail("number_coins should not be greater than $user_pivot->max_buy_additional_coins_cycle (max_buy_additional_coins_cycle)");
                        }
                        if ($this->number_coins > $user_pivot->max_buy_additional_coins_game) {
                            $fail("number_coins should not be greater than $user_pivot->max_buy_additional_coins_game (max_buy_additional_coins_game)");
                        }
                    } else {
                        if ($this->number_coins > $user_pivot->max_buy_coins_cycle) {
                            $fail("number_coins should not be greater than $user_pivot->max_buy_coins_cycle (max_buy_coins_cycle)");
                        }
                        if ($this->number_coins > $user_pivot->max_buy_coins_game) {
                            $fail("number_coins should not be greater than $user_pivot->max_buy_coins_game (max_buy_coins_game)");
                        }
                    }
                    if ($this->number_coins > $coin->buy_to_bank_coins) {
                        $fail("the bank has only {$coin->buy_to_bank_coins} coins");
                    }
                }
            ],
            'additional_coins' => 'required|boolean',
        ];
    }
}
