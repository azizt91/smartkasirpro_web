<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AuditAlert extends Notification
{
    use Queueable;

    protected $auditLog;

    /**
     * Create a new notification instance.
     */
    public function __construct($auditLog)
    {
        $this->auditLog = $auditLog;
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
        return [
            'title' => '⚠️ Peringatan Sistem: ' . $this->auditLog->action,
            'body' => "Aksi: {$this->auditLog->action} oleh Admin/Kasir.",
            'audit_log_id' => $this->auditLog->id,
            'type' => 'audit_alert',
        ];
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm(object $notifiable)
    {
        return [
            'title' => '⚠️ Alert: ' . $this->auditLog->action,
            'body' => "Aktivitas penting tercatat: {$this->auditLog->action}",
            'data' => [
                'audit_log_id' => (string) $this->auditLog->id,
                'type' => 'audit_alert',
                'notification_type' => 'audit',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ]
        ];
    }
}
