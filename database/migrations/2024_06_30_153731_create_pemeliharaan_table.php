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
        Schema::create('pemeliharaan', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('umur');
            $table->bigInteger('jumlah_ayam');
            $table->bigInteger('afkir');
            $table->bigInteger('sisa');
            $table->bigInteger('mati');
            $table->longText('keterangan');

            $table->bigInteger('kandang_id');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemeliharaan');
    }
};
