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
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->string('product_name')->nullable()->comment('Name of product at time of transaction');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete()->comment('Pegawai yang mengerjakan jasa');
            $table->decimal('commission_amount', 15, 2)->default(0)->comment('Nominal asli komisi yang didapat saat transaksi');
            $table->foreignId('settlement_id')->nullable()->constrained('commission_settlements')->nullOnDelete();
            $table->integer('quantity');
            $table->decimal('price', 10, 2)->comment('Selling price at time of transaction');
            $table->decimal('subtotal', 10, 2)->comment('Quantity * Price');
            $table->timestamps();
            
            $table->index(['transaction_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};