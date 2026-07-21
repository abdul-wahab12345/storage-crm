<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\WaMessage;

class AdminNewWhatsAppMessageNotification extends Notification
{
    use Queueable;

    public function __construct(public WaMessage $waMessage) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $chat = $this->waMessage->chat;
        $contactName = $chat->contact_name ?? $chat->contact_phone;
        
        return (new MailMessage)
            ->subject("New WhatsApp Message from {$contactName}")
            ->line("You have received a new WhatsApp message from {$contactName}.")
            ->line("**Message:**")
            ->line($this->waMessage->body ?? '[Media Message]')
            ->action('View Inbox', url('/admin/whats-app-inbox'))
            ->line('Please reply within 24 hours to keep the session open.');
    }

}
