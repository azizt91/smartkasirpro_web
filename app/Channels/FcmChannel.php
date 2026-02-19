<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class FcmChannel
{
    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toFcm')) {
            return;
        }

        $message = $notification->toFcm($notifiable);
        $token = $notifiable->fcm_token;

        if (!$token || !$message) {
            return;
        }

        // If message is array, convert to CloudMessage? 
        // Or assume toFcm returns CloudMessage.
        // Let's assume toFcm returns array for title/body and data.

        try {
             $messaging = app('firebase.messaging');
             
             // If $message is already a CloudMessage instance
             if ($message instanceof CloudMessage) {
                 // Set target if not set
                 if (!$message->hasTarget()) {
                     $message = $message->withChangedTarget('token', $token);
                 }
                 $messaging->send($message);
             } 
             // If simple array
             else if (is_array($message)) {
                 $fcmMsg = CloudMessage::withTarget('token', $token)
                    ->withNotification(FcmNotification::create($message['title'], $message['body']))
                    ->withData($message['data'] ?? []);
                 
                 $messaging->send($fcmMsg);
             }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('FCM Send Error: ' . $e->getMessage());
        }
    }
}
