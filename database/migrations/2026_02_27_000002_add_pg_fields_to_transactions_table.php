<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('pg_provider', 20)->nullable()->after('points_discount_amount');
            $table->string('pg_reference')->nullable()->after('pg_provider');
            $table->text('pg_pay_url')->nullable()->after('pg_reference');
            $table->timestamp('pg_expired_at')->nullable()->after('pg_pay_url');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['pg_provider', 'pg_reference', 'pg_pay_url', 'pg_expired_at']);
        });
    }
};
