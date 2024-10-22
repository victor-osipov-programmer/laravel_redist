<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class СreateСoinBalance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $coin = $request->route('coin');
        $user = $request->user();
        $user_coin = $user->coins->find($coin->id);

        if (!isset($user_coin)) {
            $user->coins()->attach($coin->id, [
                'coins' => 0,
                'max_buy_coins_cycle' => $coin->max_buy_coins_cycle,
                'max_buy_coins_game' => $coin->max_buy_coins_game,
                'max_buy_additional_coins_cycle' => $coin->max_buy_additional_coins_cycle,
                'max_buy_additional_coins_game' => $coin->max_buy_additional_coins_game,
            ]);
            $user->refresh();
        }

        return $next($request);
    }
}
