<?php

namespace App\Http\Requests;

use App\Models\Coin;
use App\Models\User;
use App\Rules\RestrictionsNumberCoins;
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
        return [
            'number_coins' => [
                'required', 
                'integer',
                new RestrictionsNumberCoins($this->route('coin'))
            ],
            'additional_coins' => 'required|boolean',
        ];
    }
}
