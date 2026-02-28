<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commission_settlements', function (Blueprint $table) {
            $table->string('payment_source', 20)->default('tunai')->after('payment_date');
            $table->unsignedBigInteger('settled_by')->nullable()->after('payment_source');
        });
    }

    public function down(): void
    {
        Schema::table('commission_settlements', function (Blueprint $table) {
            $table->dropColumn(['payment_source', 'settled_by']);
        });
    }
};
