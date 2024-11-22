<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class Coin extends Model
{
    protected $guarded = [];

    function users() {
        return $this->belongsToMany(User::class, 'coins_users')->withPivot('coins', 'max_buy_coins_cycle', 'max_buy_coins_game', 'max_buy_additional_coins_cycle', 'max_buy_additional_coins_game');
    }

    function userCoins(): Attribute
    {
        return new Attribute(
            get: function () {
                $user = Request::user();
                $coin_user = $this->users->find($user->id);

                if (isset($coin_user)) {
                    return $coin_user->pivot->coins;
                } else {
                    return 0;
                }
            },
        );
    }

    // protected $appends = ['user_coins'];
}
