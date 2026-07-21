<?php

namespace App\Notifications;

use App\Channels\WhatsAppChannel;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Invoice $invoice,
        public int $daysBefore
    ) {}

    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->email) {
            $channels[] = 'mail';
        }

        if ($notifiable->whatsapp_number) {
            $channels[] = WhatsAppChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $invoice = $this->invoice;
        $daysText = $this->daysBefore === 0 ? 'today' : "in {$this->daysBefore} days";
        $adminPhone = Setting::get('company_phone', '');
        $contactText = $adminPhone ? " For any queries, please contact us at {$adminPhone}." : '';

        $mail = (new MailMessage)
            ->subject("Payment Reminder: Invoice {$invoice->invoice_number} is due {$daysText}")
            ->greeting("Hello {$notifiable->full_name},")
            ->line("This is a friendly reminder that your invoice is due {$daysText}.")
            ->line("**Invoice #:** {$invoice->invoice_number}")
            ->line("**Amount Due:** " . Setting::currency() . number_format((float) $invoice->total, 2))
            ->line("**Due Date:** {$invoice->due_date->format('F j, Y')}")
            ->action('View Invoice', url("/admin/invoices/{$invoice->id}"))
            ->line("Please make your payment as soon as possible to avoid any late fees.{$contactText}");

        try {
            $pdfPath = app(InvoicePdfService::class)->generateAndStore($invoice);
            $mail->attachFromStorageDisk('local', $pdfPath, "invoice-{$invoice->invoice_number}.pdf", [
                'mime' => 'application/pdf',
            ]);
        } catch (\Throwable) {
            // PDF attachment is best-effort
        }

        return $mail;
    }

    public function toWhatsApp(object $notifiable): array
    {
        $invoice = $this->invoice;
        $pdfService = app(InvoicePdfService::class);
        
        try {
            $pdfService->generateAndStore($invoice);
        } catch (\Throwable) {}

        return [
            'tenant_name' => mb_substr($notifiable->full_name, 0, 30),
            'invoice_number' => mb_substr($invoice->invoice_number, 0, 30),
            'amount_due' => mb_substr(Setting::currency() . number_format((float) $invoice->total, 2), 0, 30),
            'due_date' => mb_substr($invoice->due_date->format('M j, Y'), 0, 30),
            'days_remaining' => (string) $this->daysBefore,
            'pdf_url' => $pdfService->publicUrl($invoice),
            '_template_name' => Setting::get('reminder_whatsapp_template', 'payment_reminder'),
            '_language_code' => Setting::get('reminder_whatsapp_language', 'en_US'),
        ];
    }
}


