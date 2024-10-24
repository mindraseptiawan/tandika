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
        Schema::table('kandang', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->after('id')->nullable();

            // Jika Anda ingin menambahkan foreign key constraint
            // Pastikan tabel 'roles' sudah ada dan memiliki kolom 'id'
            // $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kandang', function (Blueprint $table) {
            // Jika Anda menambahkan foreign key constraint, hapus terlebih dahulu
            // $table->dropForeign(['role_id']);

            $table->dropColumn('role_id');
        });
    }
};
