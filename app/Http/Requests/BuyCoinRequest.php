<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class BuyCoinRequest extends FormRequest
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
        return [
            'number_coins' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, Closure $fail) {
                    $user = $this->user();
                    $price_coins = $this->number_coins * $this->price_coin;

                    if ($user->balance < $price_coins) {
                        $fail("Ваш баланс $user->balance, а вам нужно $price_coins");
                    }
                }
            ],
            'price_coin' => [
                'required',
                'decimal:0,2',
                'min:0.01'
            ]
        ];
    }
}
