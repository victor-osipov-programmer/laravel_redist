<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoinController;
use App\Http\Controllers\EmailVerificationNotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerifyEmailController;
use App\Http\Middleware\СreateСoinBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/coin', [CoinController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/coin', [CoinController::class, 'store']);
    Route::get('/user', [UserController::class, 'show']);
    Route::patch('/user/name', [UserController::class, 'update_name']);
    Route::patch('/user/email', [UserController::class, 'update_email']);
    Route::get('/user/code', [UserController::class, 'get_code']);

    Route::middleware(СreateСoinBalance::class)->group(function () {
        Route::post('/coin/{coin}/buy', [CoinController::class, 'buy']);
        Route::post('/coin/{coin}/sell', [CoinController::class, 'sell']);
        Route::post('/coin/{coin}/buy/bank', [CoinController::class, 'buy_to_bank']);
        Route::post('/coin/{coin}/sell/bank', [CoinController::class, 'sell_to_bank']);
    }); 
    
    Route::post('/coin/{coin}/test', [CoinController::class, 'test']);
    Route::get('/order', [OrderController::class, 'index']);
    Route::get('/order', [OrderController::class, 'index']);
    Route::get('/order/{order}/cancel', [OrderController::class, 'cancel']);
});

Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware('auth');
Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)->middleware(['signed'])->name('verification.verify');