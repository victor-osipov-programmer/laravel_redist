<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class SellCoinRequest extends FormRequest
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
                function ($attribute, $value, Closure $fail)  {
                    $user = $this->user();
                    $coin = $this->route('coin');

                    if ($user->coins->find($coin->id)->pivot->coins < $value) {
                        $coins = $user->coins->find($coin->id)->pivot->coins;

                        $fail("You have only $coins coins on your balance");
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
