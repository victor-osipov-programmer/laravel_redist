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
        Schema::create('coins', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();
            $table->unsignedInteger('total_coins');
            $table->unsignedInteger('buy_to_bank_coins');
            $table->unsignedInteger('sale_to_bank_coins');

            $table->unsignedInteger('current_cycle')->default(1);
            $table->unsignedInteger('one_cycle')->default(config('global.day_in_seconds'));
            $table->unsignedInteger('total_cycles')->nullable()->default(null);

            $table->float('price_sale_coin')->unsigned();

            $table->float('price_buy_coin')->unsigned();
            $table->unsignedInteger('max_buy_coins_cycle');
            $table->unsignedInteger('max_buy_coins_game');

            $table->float('price_buy_additional_coin')->unsigned();
            $table->unsignedInteger('max_buy_additional_coins_cycle');
            $table->unsignedInteger('max_buy_additional_coins_game');

            $table->float('expenses')->unsigned();
            $table->float('income')->unsigned()->default(0);
            $table->unsignedInteger('min_number_coins_sale');
            $table->unsignedTinyInteger('commission')->default(1);

            $table->unsignedBigInteger('creator_id');

            $table->foreign('creator_id', 'foreign_creator_id')->on('users')->references('id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coins');
    }
};
