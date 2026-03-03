<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'account_id',
        'debit',
        'credit',
        'reference_type',
        'reference_id',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
