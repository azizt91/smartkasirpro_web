<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            // ASSETS (Harta) - Debit
            ['code' => '101', 'name' => 'Kas Tunai', 'type' => 'asset', 'default_balance' => 'debit'],
            ['code' => '102', 'name' => 'Kas Bank / Digital', 'type' => 'asset', 'default_balance' => 'debit'],
            ['code' => '103', 'name' => 'Piutang Pelanggan', 'type' => 'asset', 'default_balance' => 'debit'],
            ['code' => '104', 'name' => 'Persediaan Barang', 'type' => 'asset', 'default_balance' => 'debit'],

            // LIABILITIES (Kewajiban / Hutang) - Credit
            ['code' => '201', 'name' => 'Hutang Supplier', 'type' => 'liability', 'default_balance' => 'credit'],

            // EQUITY (Modal) - Credit
            ['code' => '301', 'name' => 'Modal Pemilik', 'type' => 'equity', 'default_balance' => 'credit'],
            ['code' => '302', 'name' => 'Laba Ditahan', 'type' => 'equity', 'default_balance' => 'credit'],

            // REVENUE (Pendapatan) - Credit
            ['code' => '401', 'name' => 'Penjualan', 'type' => 'revenue', 'default_balance' => 'credit'],

            // EXPENSES (Beban) - Debit
            ['code' => '501', 'name' => 'HPP (Harga Pokok Penjualan)', 'type' => 'expense', 'default_balance' => 'debit'],
            ['code' => '502', 'name' => 'Beban Operasional Lainnya', 'type' => 'expense', 'default_balance' => 'debit'],
        ];

        foreach ($accounts as $account) {
            Account::firstOrCreate(
                ['code' => $account['code']],
                $account
            );
        }
    }
}
