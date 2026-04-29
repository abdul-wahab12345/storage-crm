<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhatsAppService
{
    protected string $accessToken;
    protected string $phoneNumberId;
    protected string $apiVersion;
    protected string $templateName;

    public function __construct()
    {
        $this->accessToken   = config('services.whatsapp.access_token', '');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id', '');
        $this->apiVersion    = config('services.whatsapp.api_version', 'v19.0');
        $this->templateName  = config('services.whatsapp.template_name', 'invoice_generated');
    }

    /**
     * Send invoice notification via WhatsApp:
     *   1. Template message (required for business-initiated conversation)
     *   2. PDF document message (if a public URL is provided)
     *
     * Template variables (in order):
     *   {{1}} customer name  {{2}} invoice number  {{3}} amount due  {{4}} due date
     */
    public function sendInvoiceNotification(
        string $to,
        string $invoiceNumber,
        string $amountDue,
        string $dueDate,
        string $tenantName = '',
        ?string $pdfPublicUrl = null,
    ): bool {
        if (empty($this->accessToken) || empty($this->phoneNumberId)) {
            Log::warning('WhatsApp: credentials not configured; skipping notification.');
            return false;
        }

        $phone = $this->normalizePhone($to);

        // 1. Send template message
        $templateSent = $this->sendTemplate($phone, $tenantName, $invoiceNumber, $amountDue, $dueDate);

        // 2. Optionally send the PDF as a document (works once the conversation is open)
        if ($templateSent && $pdfPublicUrl) {
            $this->sendDocument($phone, $pdfPublicUrl, "invoice-{$invoiceNumber}.pdf");
        }

        return $templateSent;
    }

    protected function sendTemplate(
        string $to,
        string $tenantName,
        string $invoiceNumber,
        string $amountDue,
        string $dueDate
    ): bool {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'   => $to,
            'type' => 'template',
            'template' => [
                'name'     => $this->templateName,
                'language' => ['code' => 'en_US'],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $tenantName ?: 'Customer'],
                            ['type' => 'text', 'text' => $invoiceNumber],
                            ['type' => 'text', 'text' => $amountDue],
                            ['type' => 'text', 'text' => $dueDate],
                        ],
                    ],
                ],
            ],
        ];

        return $this->post($payload, $to, 'template');
    }

    protected function sendDocument(string $to, string $documentUrl, string $filename): bool
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'   => $to,
            'type' => 'document',
            'document' => [
                'link'     => $documentUrl,
                'filename' => $filename,
            ],
        ];

        return $this->post($payload, $to, 'document');
    }

    protected function post(array $payload, string $to, string $type): bool
    {
        try {
            $response = Http::timeout(15)
                ->withToken($this->accessToken)
                ->post(
                    "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages",
                    $payload
                );

            if ($response->successful()) {
                Log::info("WhatsApp {$type} sent to {$to}");
                return true;
            }

            Log::error("WhatsApp {$type} API error", [
                'to'     => $to,
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);
        } catch (\Throwable $e) {
            Log::error("WhatsApp {$type} exception: " . $e->getMessage(), ['to' => $to]);
        }

        return false;
    }

    protected function normalizePhone(string $phone): string
    {
        return preg_replace('/[^\d]/', '', $phone);
    }
}
