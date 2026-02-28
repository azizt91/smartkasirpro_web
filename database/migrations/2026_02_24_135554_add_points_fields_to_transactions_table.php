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
        Schema::table('transactions', function (Blueprint $table) {
            $table->integer('points_earned')->default(0)->comment('Poin yang didapatkan dari transaksi ini');
            $table->integer('points_redeemed')->default(0)->comment('Poin yang digunakan (ditukarkan)');
            $table->decimal('points_discount_amount', 15, 2)->default(0)->comment('Potongan harga (rupiah) dari penukaran poin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['points_earned', 'points_redeemed', 'points_discount_amount']);
        });
    }
};
