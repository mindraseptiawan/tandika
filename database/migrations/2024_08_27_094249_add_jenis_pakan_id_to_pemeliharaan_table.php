<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('pemeliharaan', function (Blueprint $table) {
            $table->unsignedBigInteger('jenis_pakan_id')->nullable()->after('kandang_id');

            // Menambahkan foreign key constraint
            $table->foreign('jenis_pakan_id')->references('id')->on('pakan')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('pemeliharaan', function (Blueprint $table) {
            $table->dropForeign(['jenis_pakan_id']);
            $table->dropColumn('jenis_pakan_id');
        });
    }
};
