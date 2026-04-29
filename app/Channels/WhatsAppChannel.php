<?php

namespace App\Channels;

use App\Services\WhatsAppService;
use Illuminate\Notifications\Notification;

class WhatsAppChannel
{
    public function __construct(protected WhatsAppService $whatsApp) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $to = $notifiable->routeNotificationFor('whatsApp', $notification)
            ?? $notifiable->whatsapp_number
            ?? null;

        if (! $to) {
            return;
        }

        $data = $notification->toWhatsApp($notifiable);

        $this->whatsApp->sendInvoiceNotification(
            to: $to,
            invoiceNumber: $data['invoice_number'],
            amountDue: $data['amount_due'],
            dueDate: $data['due_date'],
            tenantName: $data['tenant_name'] ?? '',
            pdfPublicUrl: $data['pdf_url'] ?? null,
        );
    }
}
