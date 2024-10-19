<?php

namespace App\Rules;

use App\Models\Coin;
use Auth;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class RestrictionsNumberCoins implements ValidationRule, DataAwareRule
{
    protected $data = [];
    protected $coin = [];


    function __construct(Coin $coin) {
        $this->coin = $coin;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = Auth::user();
        $user_pivot = $user->coins->find($this->coin->id)->pivot;

        if ($this->data['additional_coins']) {
            if ($this->data['number_coins'] > $user_pivot->max_buy_additional_coins_cycle) {
                $fail("$attribute should not be greater than $user_pivot->max_buy_additional_coins_cycle (max_buy_additional_coins_cycle)");
            }
            if ($this->data['number_coins'] > $user_pivot->max_buy_additional_coins_game) {
                $fail("$attribute should not be greater than $user_pivot->max_buy_additional_coins_game (max_buy_additional_coins_game)");
            }
        } else {
            if ($this->data['number_coins'] > $user_pivot->max_buy_coins_cycle) {
                $fail("$attribute should not be greater than $user_pivot->max_buy_coins_cycle (max_buy_coins_cycle)");
            }
            if ($this->data['number_coins'] > $user_pivot->max_buy_coins_game) {
                $fail("$attribute should not be greater than $user_pivot->max_buy_coins_game (max_buy_coins_game)");
            }
        }
        if ($this->data['number_coins'] > $this->coin->buy_to_bank_coins) {
            $fail("the bank has only {$this->coin->buy_to_bank_coins} coins");
        }
    }

    public function setData(array $data): static
    {
        $this->data = $data;
 
        return $this;
    }
}
