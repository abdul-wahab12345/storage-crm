<?php

namespace App\Notifications;

use App\Channels\WebhookChannel;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Invoice $invoice,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->email) {
            $channels[] = 'mail';
        }

        $facility = $this->invoice->lease?->unit?->facility;
        if ($facility?->webhook_url) {
            $channels[] = WebhookChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $invoice = $this->invoice;
        $pdfPath = app(InvoicePdfService::class)->generateAndStore($invoice);

        return (new MailMessage)
            ->subject("Invoice {$invoice->invoice_number} — StorageCRM")
            ->greeting("Hello {$notifiable->full_name},")
            ->line("A new invoice has been generated for your storage unit.")
            ->line("**Invoice #:** {$invoice->invoice_number}")
            ->line("**Amount Due:** $" . number_format($invoice->total, 2))
            ->line("**Due Date:** {$invoice->due_date->format('F j, Y')}")
            ->line("**Period:** {$invoice->period_start->format('M j')} — {$invoice->period_end->format('M j, Y')}")
            ->action('View Invoice', url("/admin/invoices/{$invoice->id}"))
            ->line('Please make your payment by the due date to avoid late fees.')
            ->attach(storage_path("app/{$pdfPath}"), [
                'as' => "invoice-{$invoice->invoice_number}.pdf",
                'mime' => 'application/pdf',
            ]);
    }

    public function toWebhook(object $notifiable): array
    {
        $invoice = $this->invoice;
        $facility = $invoice->lease?->unit?->facility;

        return [
            'webhook_url' => $facility?->webhook_url,
            'event' => 'invoice.generated',
            'tenant' => [
                'name' => $notifiable->full_name,
                'phone' => $notifiable->phone,
                'email' => $notifiable->email,
            ],
            'invoice' => [
                'number' => $invoice->invoice_number,
                'amount' => (float) $invoice->amount,
                'late_fee' => (float) $invoice->late_fee,
                'total' => (float) $invoice->total,
                'due_date' => $invoice->due_date->toDateString(),
                'period_start' => $invoice->period_start->toDateString(),
                'period_end' => $invoice->period_end->toDateString(),
                'status' => $invoice->status,
            ],
            'unit' => [
                'number' => $invoice->lease?->unit?->unit_number,
                'size' => $invoice->lease?->unit?->size,
            ],
            'facility' => [
                'name' => $facility?->name,
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'total' => $this->invoice->total,
        ];
    }
}
