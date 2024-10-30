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

class ExecuteBuyOrderJob implements ShouldQueue
{
    use Queueable;

    public $buy_order;
    public $coin;

    public function __construct(Order $buy_order, $coin)
    {
        $this->buy_order = $buy_order;
        $this->coin = $coin;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $buy_order = $this->buy_order;
        $coin = $this->coin;
        $view_order = new Order;
        $view_order->table = 'view_orders';

        $sell_orders = $view_order
            ->where('type', 'sell')
            ->where('price_coin', '<=', $buy_order->price_coin)
            ->where('coin_id', $coin->id)
            ->orderBy('price_coin')
            ->orderByDesc('user_donations')
            ->get();
        $received_coins = 0;


        foreach ($sell_orders as $sell_order) {
            try {
                DB::beginTransaction();
                $coins_turnover = min($sell_order->number_coins, $buy_order->number_coins);
                $price_coins_without_commission = $coins_turnover * $buy_order->price_coin;
                $commission = $price_coins_without_commission * ($coin->commission / 100);
                $price_coins = $price_coins_without_commission - $commission;

                Log::info("1coin->income $coin->income");
                Log::info("1commission $commission");
                $coin->update([
                    'income' => $coin->income + $commission
                ]);
                Log::info("1coin->income $coin->income");

                $sell_order->user->update([
                    'balance' => $sell_order->user->balance + $price_coins
                ]);
                $buy_order->user->coins()->updateExistingPivot($coin->id, [
                    'coins' => $buy_order->user->coins->find($coin->id)->pivot->coins + $coins_turnover
                ]);
                $buy_order->refresh();

                $sell_order_number_coins = $sell_order->number_coins - $coins_turnover;
                if ($sell_order_number_coins == 0) {
                    $sell_order->table = 'orders';
                    $sell_order->delete();
                } else {
                    $sell_order->update([
                        'number_coins' => $sell_order_number_coins
                    ]);
                }

                $buy_order_number_coins = $buy_order->number_coins - $coins_turnover;
                if ($buy_order_number_coins == 0) {
                    $buy_order->table = 'orders';
                    $buy_order->delete();
                } else {
                    $buy_order->update([
                        'number_coins' => $buy_order_number_coins
                    ]);
                }



                $received_coins += $coins_turnover;
                DB::commit();

                Buy::dispatch($coin, $buy_order, [
                    'number_coins' => $coins_turnover,
                    'price_coins' => $price_coins_without_commission
                ]);
                Sell::dispatch($coin, $sell_order, [
                    'number_coins' => $coins_turnover,
                    'price_coins' => $price_coins_without_commission,
                    'commission' => $commission
                ]);

                if ($buy_order_number_coins == 0) {
                    // return [
                    //     'message' => 'Buy order completed',
                    //     'received_coins' => $received_coins,
                    //     'sell_orders' => $sell_orders
                    // ];
                }
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }

        // return response([
        //     'message' => 'Created buy order',
        //     'received_coins' => $received_coins,
        //     'sell_orders' => $sell_orders
        // ], 201);
    }
}
