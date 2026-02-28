<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Provider aktif (none = semua off)
            $table->string('pg_active', 20)->default('none')->after('enable_loyalty_points');
            // Mode sandbox/production
            $table->string('pg_mode', 20)->default('sandbox')->after('pg_active');
            // Siapa yg menanggung biaya admin
            $table->string('pg_fee_bearer', 20)->default('customer')->after('pg_mode');

            // Tripay credentials
            $table->text('tripay_api_key')->nullable()->after('pg_fee_bearer');
            $table->text('tripay_private_key')->nullable()->after('tripay_api_key');
            $table->string('tripay_merchant_code')->nullable()->after('tripay_private_key');

            // Duitku credentials
            $table->string('duitku_merchant_code')->nullable()->after('tripay_merchant_code');
            $table->text('duitku_api_key')->nullable()->after('duitku_merchant_code');

            // Midtrans credentials
            $table->text('midtrans_client_key')->nullable()->after('duitku_api_key');
            $table->text('midtrans_server_key')->nullable()->after('midtrans_client_key');
            $table->string('midtrans_merchant_id')->nullable()->after('midtrans_server_key');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'pg_active', 'pg_mode', 'pg_fee_bearer',
                'tripay_api_key', 'tripay_private_key', 'tripay_merchant_code',
                'duitku_merchant_code', 'duitku_api_key',
                'midtrans_client_key', 'midtrans_server_key', 'midtrans_merchant_id',
            ]);
        });
    }
};
