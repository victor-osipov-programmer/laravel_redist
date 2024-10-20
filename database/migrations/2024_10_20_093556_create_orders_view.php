<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement($this->createView());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement($this->dropView());
    }


    private function createView(): string
    {
        return <<<SQL
            CREATE VIEW view_orders AS
            SELECT orders.*, number_coins * price_coin as price_coins, users.donations as user_donations
            FROM orders
            JOIN users ON users.id = orders.user_id
        SQL;
    }
   
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    private function dropView(): string
    {
        return <<<SQL
            DROP VIEW IF EXISTS `view_orders`;
        SQL;
    }
};
