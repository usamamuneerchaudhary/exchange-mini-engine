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
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buy_order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('sell_order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('symbol', 10);
            $table->decimal('price', 20, 2);
            $table->decimal('amount', 20, 8);
            $table->decimal('commission', 20, 2);
            $table->timestamps();

            $table->index('symbol');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
