<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected static function booted()
    {
        static::created(function ($auditLog) {
            $criticalActions = [
                'Delete Transaction',
                'Manual Stock Adjustment',
                'Commission Payment'
            ];

            if (in_array($auditLog->action, $criticalActions)) {
                // Determine users to notify
                $usersToNotify = \App\Models\User::whereNotNull('fcm_token')
                    ->where('fcm_token', '!=', '')
                    ->get();
                    
                if ($usersToNotify->isNotEmpty()) {
                    \Illuminate\Support\Facades\Notification::send($usersToNotify, new \App\Notifications\AuditAlert($auditLog));
                }
            }
        });
    }

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
