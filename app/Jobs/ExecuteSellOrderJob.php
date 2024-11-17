<?php

namespace App\Jobs;

use App\Events\Buy;
use App\Events\Sell;
use App\Models\Order;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExecuteSellOrderJob implements ShouldQueue
{
    use Queueable;


    public $sell_order;
    public $coin;

    public function __construct(Order $sell_order)
    {
        $this->sell_order = $sell_order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sell_order = $this->sell_order;
        $coin = $this->sell_order->coin;
        $view_order = new Order;
        $view_order->table = 'view_orders';

        Log::info($sell_order);
        Log::info($sell_order->coin);

        $buy_orders = $view_order
            ->where('type', 'buy')
            ->where('price_coin', '>=', $sell_order->price_coin)
            ->where('coin_id', $coin->id)
            ->orderByDesc('price_coin')
            ->orderByDesc('user_donations')
            ->get();
        $received_currency = 0;
        $shared_commision = 0;


        foreach ($buy_orders as $buy_order) {
            try {
                DB::beginTransaction();
                $coins_turnover = min($sell_order->number_coins, $buy_order->number_coins);
                $price_coins_without_commission = $coins_turnover * $buy_order->price_coin;
                $commission = $price_coins_without_commission * ($coin->commission / 100);
                $price_coins = $price_coins_without_commission - $commission;

                $coin->update([
                    'income' => $coin->income + $commission
                ]);

                $sell_order->user->update([
                    'balance' => $sell_order->user->balance + $price_coins
                ]);
                $buy_order->user->coins()->updateExistingPivot($coin->id, [
                    'coins' => $buy_order->user->coins->find($coin->id)->pivot->coins + $coins_turnover
                ]);
                $buy_order->refresh();

                $sell_order_number_coins = $sell_order->number_coins - $coins_turnover;
                $sell_order->update([
                    'number_coins' => $sell_order_number_coins,
                    'status' => $sell_order_number_coins == 0 ? 'completed' : null
                ]);
                if ($sell_order_number_coins == 0) {
                    $sell_order->table = 'orders';
                    $sell_order->delete();
                }

                $buy_order_number_coins = $buy_order->number_coins - $coins_turnover;
                $buy_order->update([
                    'number_coins' => $buy_order_number_coins,
                    'status' => $buy_order_number_coins == 0 ? 'completed' : null
                ]);
                if ($buy_order_number_coins == 0) {
                    $buy_order->table = 'orders';
                    $buy_order->delete();
                }


                $shared_commision += $commission;
                $received_currency += $price_coins;
                DB::commit();

                Buy::dispatch($buy_order, [
                    'number_coins' => $coins_turnover,
                    'price_coins' => $price_coins_without_commission
                ]);
                Sell::dispatch($sell_order, [
                    'number_coins' => $coins_turnover,
                    'price_coins' => $price_coins_without_commission,
                    'commission' => $commission
                ]);

                if ($sell_order_number_coins == 0) {
                    // return [
                    //     'message' => 'Sell order completed',
                    //     'received_currency' => $received_currency,
                    //     'commission' => $shared_commision,
                    //     'buy_orders' => $buy_orders
                    // ];
                }
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }

        // return response([
        //     'message' => 'Created sell order',
        //     'received_currency' => $received_currency,
        //     'commission' => $shared_commision,
        //     'buy_orders' => $buy_orders
        // ], 201);
    }
}
