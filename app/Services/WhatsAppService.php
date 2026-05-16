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

        return $this->sendTemplate($phone, $tenantName, $invoiceNumber, $amountDue, $dueDate, $pdfPublicUrl);
    }

    protected function sendTemplate(
        string $to,
        string $tenantName,
        string $invoiceNumber,
        string $amountDue,
        string $dueDate,
        ?string $pdfPublicUrl = null,
    ): bool {
        $components = [];

        if ($pdfPublicUrl) {
            $components[] = [
                'type' => 'header',
                'parameters' => [[
                    'type'     => 'document',
                    'document' => [
                        'link'     => $pdfPublicUrl,
                        'filename' => mb_substr("invoice-{$invoiceNumber}.pdf", 0, 30),
                    ],
                ]],
            ];
        }

        // Body variables: {{1}} name  {{2}} invoice#  {{3}} amount  {{4}} due date
        // WhatsApp enforces a 30-character limit on each text body parameter.
        $components[] = [
            'type' => 'body',
            'parameters' => $this->textParams([
                $tenantName ?: 'Customer',
                $invoiceNumber,
                $amountDue,
                $dueDate,
            ]),
        ];

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'   => $to,
            'type' => 'template',
            'template' => [
                'name'       => $this->templateName,
                'language'   => ['code' => config('services.whatsapp.language_code', 'en_US')],
                'components' => $components,
            ],
        ];

        return $this->post($payload, $to, 'template');
    }

    /**
     * Generic template sender for marketing campaigns.
     * $bodyParams: [['type'=>'text','text'=>'value'], ...]
     */
    public function sendTemplateMessage(
        string $to,
        string $templateName,
        string $languageCode,
        array $bodyParams,
        ?string $headerDocUrl = null,
        ?string $headerDocFilename = null,
    ): bool {
        if (empty($this->accessToken) || empty($this->phoneNumberId)) {
            Log::warning('WhatsApp: credentials not configured; skipping.');
            return false;
        }

        $phone      = $this->normalizePhone($to);
        $components = [];

        if ($headerDocUrl) {
            $components[] = [
                'type'       => 'header',
                'parameters' => [[
                    'type'     => 'document',
                    'document' => [
                        'link'     => $headerDocUrl,
                        'filename' => $headerDocFilename ?? 'document.pdf',
                    ],
                ]],
            ];
        }

        // Sanitise every text param here — callers shouldn't need to worry about limits
        $safe = array_map(function (array $param): array {
            if (($param['type'] ?? '') === 'text') {
                $param['text'] = mb_substr(trim($param['text'] ?? '') ?: '-', 0, 30);
            }
            return $param;
        }, $bodyParams);

        if (! empty($safe)) {
            $components[] = ['type' => 'body', 'parameters' => $safe];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $phone,
            'type'              => 'template',
            'template'          => [
                'name'       => $templateName,
                'language'   => ['code' => $languageCode],
                'components' => $components,
            ],
        ];

        return $this->post($payload, $phone, 'template');
    }

    /** Build text parameter array, truncating each value to 30 chars. */
    protected function textParams(array $values): array
    {
        return array_map(fn (string $v) => [
            'type' => 'text',
            'text' => mb_substr(trim($v) ?: '-', 0, 30),
        ], $values);
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
        Log::debug('WhatsApp outgoing payload', ['payload' => $payload]);

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
