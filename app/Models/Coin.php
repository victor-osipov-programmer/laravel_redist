<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coin extends Model
{
    protected $guarded = [];

    function users() {
        return $this->belongsToMany(User::class, 'coins_users')->withPivot('coins', 'max_buy_coins_cycle', 'max_buy_coins_game', 'max_buy_additional_coins_cycle', 'max_buy_additional_coins_game');
    }
}
