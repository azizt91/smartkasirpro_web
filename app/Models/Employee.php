<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'name',
        'status',
    ];

    /**
     * Get the transaction items associated with this employee (services performed).
     */
    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    /**
     * Get the commission settlements for this employee.
     */
    public function settlements(): HasMany
    {
        return $this->hasMany(CommissionSettlement::class);
    }
}
