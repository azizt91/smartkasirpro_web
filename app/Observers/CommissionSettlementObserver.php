<?php

namespace App\Observers;

use App\Models\CommissionSettlement;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class CommissionSettlementObserver
{
    private function log(string $action, CommissionSettlement $settlement)
    {
        if (Auth::check()) {
            $oldValues = [];
            $newValues = [];

            if ($action === 'updated') {
                $oldValues = array_intersect_key($settlement->getOriginal(), $settlement->getDirty());
                $newValues = $settlement->getDirty();
            } elseif ($action === 'created') {
                $newValues = $settlement->getAttributes();
            } elseif ($action === 'deleted') {
                $oldValues = $settlement->getAttributes();
            }

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'model_type' => CommissionSettlement::class,
                'model_id' => $settlement->id,
                'old_values' => empty($oldValues) ? null : $oldValues,
                'new_values' => empty($newValues) ? null : $newValues,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }

    /**
     * Handle the CommissionSettlement "created" event.
     */
    public function created(CommissionSettlement $settlement): void
    {
        $this->log('created', $settlement);
    }

    /**
     * Handle the CommissionSettlement "updated" event.
     */
    public function updated(CommissionSettlement $settlement): void
    {
        $this->log('updated', $settlement);
    }

    /**
     * Handle the CommissionSettlement "deleted" event.
     */
    public function deleted(CommissionSettlement $settlement): void
    {
        $this->log('deleted', $settlement);
    }
}
