<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoinController;
use App\Http\Middleware\СreateСoinBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/coin', [CoinController::class, 'store']);

    Route::middleware(СreateСoinBalance::class)->group(function () {
        Route::post('/coin/{coin}/buy', [CoinController::class, 'buy']);
        Route::post('/coin/{coin}/sell', [CoinController::class, 'sell']);
        Route::post('/coin/{coin}/buy/bank', [CoinController::class, 'buy_to_bank']);
        Route::post('/coin/{coin}/sell/bank', [CoinController::class, 'sell_to_bank']);
    }); 
    
    Route::post('/coin/{coin}/test', [CoinController::class, 'test']);
});