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
        Schema::create('coins_users', function (Blueprint $table) {
            $table->unsignedBigInteger('coin_id');
            $table->unsignedBigInteger('user_id');

            $table->unsignedBigInteger('coins')->default(0);

            $table->unsignedInteger('max_buy_coins_cycle');
            $table->unsignedInteger('max_buy_coins_game');

            $table->unsignedInteger('max_buy_additional_coins_cycle');
            $table->unsignedInteger('max_buy_additional_coins_game');

            $table->foreign('coin_id', 'foreign_coin_id')->on('coins')->references('id');
            $table->foreign('user_id', 'foreign_user_id')->on('users')->references('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coins_users');
    }
};
