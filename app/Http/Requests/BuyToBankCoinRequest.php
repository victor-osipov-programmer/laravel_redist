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
                            $fail("Кол-во монет должно быть не больше $user_pivot->max_buy_additional_coins_cycle (макс. покупка доп. монет за цикл)");
                        }
                        if ($this->number_coins > $user_pivot->max_buy_additional_coins_game) {
                            $fail("Кол-во монет должно быть не больше $user_pivot->max_buy_additional_coins_game (макс. покупка доп. монет за игру)");
                        }
                    } else {
                        if ($this->number_coins > $user_pivot->max_buy_coins_cycle) {
                            $fail("Кол-во монет должно быть не больше $user_pivot->max_buy_coins_cycle (макс. покупка монет за цикл)");
                        }
                        if ($this->number_coins > $user_pivot->max_buy_coins_game) {
                            $fail("Кол-во монет должно быть не больше $user_pivot->max_buy_coins_game (макс. покупка монет за игру)");
                        }
                    }
                    if ($this->number_coins > $coin->buy_to_bank_coins) {
                        $fail("В банке всего $coin->buy_to_bank_coins монет");
                    }

                    if ($this->additional_coins) {
                        $price_coins = $value * $coin->price_buy_additional_coin;
                    } else {
                        $price_coins = $value * $coin->price_buy_coin;
                    }
                    if ($user->balance < $price_coins) {
                        $fail("Ваш баланс $user->balance, а вам нужно $price_coins");
                    }
                }
            ],
            'additional_coins' => 'required|boolean',
        ];
    }
}
