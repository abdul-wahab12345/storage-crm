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
        $templateName = $data['_template_name'] ?? null;

        if ($templateName) {
            $this->whatsApp->sendTemplateMessage(
                to: $to,
                templateName: $templateName,
                languageCode: $data['_language_code'] ?? 'en_US',
                bodyParams: [
                    ['type' => 'text', 'text' => $data['tenant_name'] ?? ''],
                    ['type' => 'text', 'text' => $data['invoice_number'] ?? ''],
                    ['type' => 'text', 'text' => $data['amount_due'] ?? ''],
                    ['type' => 'text', 'text' => $data['due_date'] ?? ''],
                    ['type' => 'text', 'text' => $data['days_remaining'] ?? ''],
                    ['type' => 'text', 'text' => \App\Models\Setting::get('company_phone', 'our support team')],
                ],
                headerDocUrl: $data['pdf_url'] ?? null,
                headerDocFilename: "invoice-" . ($data['invoice_number'] ?? 'doc') . ".pdf"
            );
        } else {
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
}
