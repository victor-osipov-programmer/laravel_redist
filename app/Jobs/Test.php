<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class Test implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        return 123;
        dd(123);
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        return 123;
        dd(123);
    }
}
