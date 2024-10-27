<?php

namespace App\Jobs;

use App\Models\Coin;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResetCoinLimitsJob implements ShouldQueue
{
    use Queueable;

    public $coin;

    public $tries = 3;
    public $maxExceptions = 3;


    public function __construct(int $coin_id)
    {
        $this->onQueue('cycles');
        try {
            $this->coin = Coin::find($coin_id);
        } catch (ModelNotFoundException $e) {
            Log::error("coin model $coin_id not found");
        }
    }

    
    public function handle()
    {
        if (isset($this->coin)) {
            DB::table('coins_users')->where('coin_id', $this->coin->id)->update([
                'max_buy_coins_cycle' => $this->coin->max_buy_coins_cycle,
                'max_buy_additional_coins_cycle' => $this->coin->max_buy_coins_cycle,
            ]);
            Log::info("coin_id: {$this->coin->id}, one_cycle {$this->coin->one_cycle}");
    
            ResetCoinLimitsJob::dispatch($this->coin->id)->delay(now()->addSeconds($this->coin->one_cycle));
        }
    }
}