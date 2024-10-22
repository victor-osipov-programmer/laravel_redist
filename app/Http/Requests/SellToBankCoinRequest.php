<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class SellToBankCoinRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $coin = $this->route('coin');
        $user_pivot = $user->coins->find($coin->id)->pivot;

        return [
            'number_coins' => [
                'required', 
                'integer',
                'min:1',
                function (string $attribute, mixed $value, Closure $fail) use ($user_pivot, $coin) {
                    if ($value < $coin->min_number_coins_sale) {
                        $fail("$attribute must not be less than $coin->min_number_coins_sale (min_number_coins_sale)");
                    }
                    if ($user_pivot->coins < $value) {
                        $fail("Your coins balance is $user_pivot->coins, but you need $value");
                    }
                    if ($coin->sale_to_bank_coins < $value) {
                        $fail("There are only $coin->sale_to_bank_coins coins in the bank");
                        $fail("Bank coins balance is $coin->sale_to_bank_coins");
                    }
                }
            ],
        ];
    }
}
