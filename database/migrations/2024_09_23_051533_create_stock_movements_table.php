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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kandang_id');
            $table->enum('type', ['in', 'out']);
            $table->integer('quantity');
            $table->enum('reason', ['purchase', 'sale', 'death', 'transfer', 'other']);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('kandang_id')->references('id')->on('kandang')->onDelete('cascade');

            $table->foreign('reference_id')
                ->references('id')
                ->on('purchases')
                ->onDelete('set null')
                ->name('stock_movements_purchase_foreign');

            $table->foreign('reference_id')
                ->references('id')
                ->on('sales')
                ->onDelete('set null')
                ->name('stock_movements_sale_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks_movement', function (Blueprint $table) {
            //
        });
    }
};
