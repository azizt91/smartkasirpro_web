<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class OrderCreated extends Notification
{

    /**
     * Create a new notification instance.
     */
    protected $transaction;

    /**
     * Create a new notification instance.
     */
    public function __construct($transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', \App\Channels\FcmChannel::class];
    }

    /**
     * Get the array representation of the notification (For Database).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->transaction->loadMissing('table');
        
        $total = number_format($this->transaction->total_amount, 0, ',', '.');
        $itemCount = $this->transaction->items->count();
        $itemsStr = $this->transaction->items->take(2)->map(function ($item) {
            return $item->product->name . " (x{$item->quantity})";
        })->implode(', ');
        
        if ($itemCount > 2) {
            $itemsStr .= ", +".($itemCount - 2)." lainnya";
        }

        $title = 'Pesanan Baru Masuk 💰';
        $body = "Rp {$total} - {$itemsStr}";

        if ($this->transaction->is_self_order) {
            $tableName = $this->transaction->table ? $this->transaction->table->nama_meja : '-';
            $custName = $this->transaction->customer_name ?: 'Pelanggan';
            $title = "[Meja {$tableName}] {$custName}";

            $paymentInfo = $this->transaction->payment_status === 'paid' 
                ? "LUNAS (" . strtoupper($this->transaction->payment_method) . ")" 
                : "BAYAR NANTI/KASIR";
                
            $body = "{$paymentInfo} • Rp {$total}\n{$itemsStr}";
        }

        return [
            'title' => $title,
            'body' => $body,
            'transaction_id' => $this->transaction->id,
            'type' => 'order_created',
        ];
    }
    
    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm(object $notifiable)
    {
        $this->transaction->loadMissing('table');
        
        $total = number_format($this->transaction->total_amount, 0, ',', '.');
        $itemCount = $this->transaction->items->count();
        // Summary items: "Kopi (x2), Roti (x1)..."
        $itemsStr = $this->transaction->items->take(2)->map(function ($item) {
            return $item->product->name . " (x{$item->quantity})";
        })->implode(', ');
        
        if ($itemCount > 2) {
            $itemsStr .= ", +".($itemCount - 2)." lainnya";
        }

        $title = 'Pesanan Baru: Rp ' . $total;
        $body = $itemsStr;

        // Customization for Self Order (Resto Mode)
        if ($this->transaction->is_self_order) {
            $tableName = $this->transaction->table ? $this->transaction->table->nama_meja : '-';
            $custName = $this->transaction->customer_name ?: 'Pelanggan';
            $title = "[Meja {$tableName}] {$custName}";

            // Payment info
            if ($this->transaction->payment_status === 'paid') {
                $paymentInfo = "LUNAS (" . strtoupper($this->transaction->payment_method) . ")";
            } else {
                $paymentInfo = "BAYAR NANTI (KASIR)";
            }
            
            $body = "{$paymentInfo} • Rp {$total}\n📋 {$itemsStr}";
        }

        return [
            'title' => $title,
            'body' => $body,
            'data' => [
                'transaction_id' => (string) $this->transaction->id,
                'type' => 'order_created',
                'notification_type' => 'order',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ]
        ];
    }
}
