<?php

namespace App\Listeners;

use App\Events\NewWhatsAppMessageReceived;
use App\Models\Setting;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AdminNewWhatsAppMessageNotification;
use App\Models\User;

class NotifyAdminOfNewWhatsAppMessage
{
    public function handle(NewWhatsAppMessageReceived $event): void
    {
        $adminEmail = Setting::get('admin_notification_email');
        if (!$adminEmail) {
            return;
        }

        // Create a dummy notifiable with the admin email
        $notifiable = new class($adminEmail) {
            public function __construct(public string $email) {}
            public function routeNotificationForMail() { return $this->email; }
        };

        Notification::send($notifiable, new AdminNewWhatsAppMessageNotification($event->waMessage));
    }
}
