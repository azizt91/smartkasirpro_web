<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ShiftClosed extends Notification
{
    use Queueable;

    protected $shift;

    /**
     * Create a new notification instance.
     */
    public function __construct($shift)
    {
        $this->shift = $shift;
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
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $actualCash = number_format($this->shift->actual_cash, 0, ',', '.');
        $difference = number_format($this->shift->difference, 0, ',', '.');
        
        return [
            'title' => 'Shift Ditutup 🔒',
            'body' => "Kasir: {$this->shift->user->name}. Setoran Fisik: Rp {$actualCash}. Selisih: Rp {$difference}.",
            'shift_id' => $this->shift->id,
            'type' => 'shift_closed',
        ];
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm(object $notifiable)
    {
        $actualCash = number_format($this->shift->actual_cash, 0, ',', '.');
        $difference = number_format($this->shift->difference, 0, ',', '.');
        
        return [
            'title' => 'Kasir Tutup Shift 🔒',
            'body' => "{$this->shift->user->name} | Fisik: Rp {$actualCash} | Selisih: Rp {$difference}",
            'data' => [
                'shift_id' => (string) $this->shift->id,
                'type' => 'shift_closed',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ]
        ];
    }
}
