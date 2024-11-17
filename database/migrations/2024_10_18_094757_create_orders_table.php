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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('coin_id');
            $table->unsignedBigInteger('user_id');

            $table->enum('type', ['sell', 'buy']);
            $table->unsignedBigInteger('number_coins');
            $table->unsignedBigInteger('initial_number_coins');
            $table->float('price_coin')->unsigned();
            $table->string('status')->nullable();

            $table->foreign('coin_id', 'foreign_target_coin_id')->on('coins')->references('id');
            $table->foreign('user_id', 'foreign_seller_id')->on('users')->references('id');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
