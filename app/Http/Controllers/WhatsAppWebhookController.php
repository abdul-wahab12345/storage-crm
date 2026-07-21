<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\WaChat;
use App\Models\WaMessage;
use App\Models\Tenant;
use App\Events\NewWhatsAppMessageReceived;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $verifyToken = env('WHATSAPP_WEBHOOK_VERIFY_TOKEN');
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode && $token) {
            if ($mode === 'subscribe' && $token === $verifyToken) {
                Log::info('WhatsApp Webhook verified.');
                return response($challenge, 200);
            }
            return response('Forbidden', 403);
        }
        return response('Bad Request', 400);
    }

    public function handle(Request $request)
    {
        $payload = $request->all();

        if (($payload['object'] ?? '') === 'whatsapp_business_account') {
            foreach ($payload['entry'] as $entry) {
                foreach ($entry['changes'] as $change) {
                    if ($change['value']['messaging_product'] === 'whatsapp') {
                        if (isset($change['value']['messages'])) {
                            $this->processMessages($change['value']['messages'], $change['value']['contacts'] ?? []);
                        }
                    }
                }
            }
            return response('EVENT_RECEIVED', 200);
        }

        return response('NOT_FOUND', 404);
    }

    protected function processMessages(array $messages, array $contacts)
    {
        $contactMap = [];
        foreach ($contacts as $contact) {
            $contactMap[$contact['wa_id']] = $contact['profile']['name'] ?? null;
        }

        foreach ($messages as $msg) {
            $from = $msg['from'];
            $msgId = $msg['id'];
            $timestamp = isset($msg['timestamp']) ? \Carbon\Carbon::createFromTimestamp($msg['timestamp']) : now();
            $type = $msg['type'];
            
            $body = null;
            if ($type === 'text') {
                $body = $msg['text']['body'] ?? null;
            } elseif (in_array($type, ['image', 'document', 'audio', 'video'])) {
                // In a full implementation we would download media here using $msg[$type]['id']
                $body = "[$type message received]";
            }

            // Upsert Chat
            $tenant = Tenant::where('whatsapp_number', $from)->first();
            $chat = WaChat::firstOrCreate(
                ['contact_phone' => $from],
                [
                    'contact_name' => $contactMap[$from] ?? null,
                    'tenant_id' => $tenant?->id,
                    'status' => 'open',
                ]
            );

            // Update chat last message info
            $chat->update([
                'last_message_at' => $timestamp,
                'last_message_body' => mb_substr($body ?? '', 0, 255),
                'status' => 'open',
            ]);

            // Insert Message
            $waMessage = WaMessage::firstOrCreate(
                ['wa_message_id' => $msgId],
                [
                    'wa_chat_id' => $chat->id,
                    'direction' => 'inbound',
                    'type' => $type,
                    'body' => $body,
                    'status' => 'received',
                    'sent_at' => $timestamp,
                ]
            );

            if ($waMessage->wasRecentlyCreated) {
                event(new NewWhatsAppMessageReceived($waMessage));
            }
        }
    }
}
