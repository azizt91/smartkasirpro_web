<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCreated extends Notification
{
    use Queueable;

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
        // Data stored in `data` column
        return [
            'title' => 'Pesanan Sukses Dibuat',
            'body' => "Pesanan {$this->transaction->transaction_code} berhasil dibuat.",
            'transaction_id' => $this->transaction->id,
            'type' => 'order_created',
        ];
    }
    
    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm(object $notifiable)
    {
        return [
            'title' => 'Pesanan Sukses Dibuat',
            'body' => "Pesanan {$this->transaction->transaction_code} berhasil dibuat.",
            'data' => [
                'transaction_id' => (string) $this->transaction->id,
                'type' => 'order_created',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ]
        ];
    }
}
