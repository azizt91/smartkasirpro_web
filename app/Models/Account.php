<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type', // 'asset', 'liability', 'equity', 'revenue', 'expense'
        'default_balance', // 'debit', 'credit'
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Hubungan ke jurnal/ledger
    public function ledgers()
    {
        return $this->hasMany(Ledger::class);
    }
}
