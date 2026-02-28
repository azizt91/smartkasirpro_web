<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionSettlement extends Model
{
    protected $fillable = [
        'employee_id',
        'amount',
        'payment_date',
        'payment_source',
        'settled_by',
        'reference_note',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function transactionItems()
    {
        return $this->hasMany(TransactionItem::class, 'settlement_id');
    }
}
