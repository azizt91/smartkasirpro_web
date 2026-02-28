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
        Schema::table('settings', function (Blueprint $table) {
            $table->integer('point_earning_rate')->default(10000)->comment('Nilai Belanja per 1 Poin (cth: 10000 = 1 Poin)');
            $table->integer('point_exchange_rate')->default(100)->comment('Nilai Tukar 1 Poin ke Rupiah (cth: 1 Poin = Rp 100)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['point_earning_rate', 'point_exchange_rate']);
        });
    }
};
