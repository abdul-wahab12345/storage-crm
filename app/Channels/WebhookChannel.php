<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWebhook')) {
            return;
        }

        $payload = $notification->toWebhook($notifiable);
        $webhookUrl = $payload['webhook_url'] ?? null;

        if (! $webhookUrl) {
            Log::warning('Webhook notification skipped: no webhook URL configured.');
            return;
        }

        unset($payload['webhook_url']);

        try {
            $response = Http::timeout(10)->post($webhookUrl, $payload);

            if ($response->failed()) {
                Log::error("Webhook notification failed: HTTP {$response->status()}", [
                    'url' => $webhookUrl,
                    'payload' => $payload,
                    'response' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("Webhook notification exception: {$e->getMessage()}", [
                'url' => $webhookUrl,
                'payload' => $payload,
            ]);
        }
    }
}
