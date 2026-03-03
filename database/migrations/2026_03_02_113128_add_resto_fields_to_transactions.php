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
            $table->foreignId('table_id')->nullable()->constrained('tables')->nullOnDelete()->after('id');
            $table->boolean('is_self_order')->default(false)->after('status');
            $table->string('customer_phone')->nullable()->after('customer_name');
            $table->enum('order_status', ['pending', 'processing', 'completed', 'cancelled'])->nullable()->after('is_self_order');
            $table->enum('payment_status', ['unpaid', 'paid'])->default('paid')->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['table_id']);
            $table->dropColumn(['table_id', 'is_self_order', 'customer_phone', 'order_status', 'payment_status']);
        });
    }
};
