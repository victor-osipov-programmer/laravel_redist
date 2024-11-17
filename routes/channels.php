<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['web', 'auth']]);

Broadcast::channel('users.{user_id}', function (User $user, int $user_id) {
    return $user->id === $user_id;
});