<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        return $user->orders;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }

    function cancel(Order $order) {
        DB::transaction(function () use($order) {
            $order->update([
                'status' => 'canceled'
            ]);

            if ($order->type == 'buy') {
                $order->user->update([
                    'balance' => $order->user->balance + $order->number_coins * $order->price_coin
                ]);
            } else {
                // Log::info($order->coin->users->find($order->user->id)->pivot->coins);
                // return $order->coin->users->find($order->user->id)->pivot->coins;
                $order->coin->users()->updateExistingPivot($order->user->id, [
                    'coins' => $order->coin->users->find($order->user->id)->pivot->coins + $order->number_coins
                ]);
            }

            $order->delete();
        });

        return [
            'message' => 'Success'
        ];
    }
}
