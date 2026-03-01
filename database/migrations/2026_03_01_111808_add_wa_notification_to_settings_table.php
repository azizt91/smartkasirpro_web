<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->text('fonnte_token')->nullable()->after('midtrans_merchant_id');
            $table->boolean('enable_wa_notification')->default(false)->after('fonnte_token');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['fonnte_token', 'enable_wa_notification']);
        });
    }
};
