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
                        $fail("$attribute должно быть не менее $coin->min_number_coins_sale (мин. кол-во монет для продажи)");
                    }
                    if ($user_pivot->coins < $value) {
                        $fail("Ваш баланс монет $user_pivot->coins, а вам нужно $value");
                    }
                    if ($coin->sale_to_bank_coins < $value) {
                        $fail("В банке есть только $coin->sale_to_bank_coins монет");
                    }
                }
            ],
        ];
    }
}
