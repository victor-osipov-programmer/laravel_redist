<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    public $table = 'orders';
    
    function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    function coin() {
        return $this->belongsTo(Coin::class, 'coin_id');
    }
}
