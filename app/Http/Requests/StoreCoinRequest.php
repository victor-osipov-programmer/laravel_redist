<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoinRequest extends FormRequest
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
            'total_coins' => 'required|integer|min:100|max:1000000',

            'one_cycle' => 'nullable|integer|min:1|max:' . config('global.year_in_seconds'),
            'total_cycles' => 'nullable|integer|min:1|max:1000000',

            'price_sale_coin' => 'required|decimal:0,2|min:0.01|max:1000000',

            'price_buy_coin' => 'required|decimal:0,2|min:0.01|max:1000000',
            'max_buy_coins_cycle' => 'nullable|integer|min:0|max:1000000',
            'max_buy_coins_game' => 'nullable|integer|min:0|max:1000000',


            'price_buy_additional_coin' => 'nullable|decimal:0,2|min:0.01|max:1000000',
            'max_buy_additional_coins_cycle' => 'nullable|integer|min:0|max:1000000',
            'max_buy_additional_coins_game' => 'nullable|integer|min:0|max:1000000',

            'min_number_coins_sale' => 'nullable|integer|min:0|max:1000000',
            'commission' => 'nullable|integer|min:1|max:100',
        ];
    }
}
