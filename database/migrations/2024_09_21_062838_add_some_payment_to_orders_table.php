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
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'transfer'])->nullable();
            $table->string('payment_proof')->nullable();
            $table->timestamp('payment_verified_at')->nullable();
            $table->unsignedBigInteger('payment_verified_by')->nullable();
            $table->foreign('payment_verified_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_method');
            $table->dropColumn('payment_proof');
            $table->dropColumn('payment_verified_at');
            $table->dropColumn('payment_verified_by');
        });
    }
};
