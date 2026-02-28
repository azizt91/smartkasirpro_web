<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class ProductObserver
{
    private function log(string $action, Product $product)
    {
        if (Auth::check()) {
            $oldValues = [];
            $newValues = [];

            if ($action === 'updated') {
                $oldValues = array_intersect_key($product->getOriginal(), $product->getDirty());
                $newValues = $product->getDirty();
            } elseif ($action === 'created') {
                $newValues = $product->getAttributes();
            } elseif ($action === 'deleted') {
                $oldValues = $product->getAttributes();
            }

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'model_type' => Product::class,
                'model_id' => $product->id,
                'old_values' => empty($oldValues) ? null : $oldValues,
                'new_values' => empty($newValues) ? null : $newValues,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }

    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        $this->log('created', $product);
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        $this->log('updated', $product);
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        $this->log('deleted', $product);
    }
}
