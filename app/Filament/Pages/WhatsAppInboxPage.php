<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class WhatsAppInboxPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Communication';
    protected static ?string $title = 'WhatsApp Inbox';
    protected static string $view = 'filament.pages.whats-app-inbox-page';

    public $chats = [];
    public $activeChatId = null;
    public $messages = [];
    public $replyBody = '';

    public function mount()
    {
        $this->loadChats();
    }

    public function loadChats()
    {
        $this->chats = \App\Models\WaChat::with('tenant')
            ->orderByDesc('last_message_at')
            ->get();
            
        if ($this->activeChatId) {
            $this->loadMessages();
        }
    }

    public function selectChat($chatId)
    {
        $this->activeChatId = $chatId;
        $this->loadMessages();
    }

    public function loadMessages()
    {
        if (!$this->activeChatId) return;

        $this->messages = \App\Models\WaMessage::where('wa_chat_id', $this->activeChatId)
            ->orderBy('sent_at', 'asc')
            ->get();
    }

    public function sendReply()
    {
        if (!$this->activeChatId || empty(trim($this->replyBody))) return;

        $chat = \App\Models\WaChat::find($this->activeChatId);
        if (!$chat) return;

        $service = app(\App\Services\WhatsAppService::class);
        $success = $service->sendTextMessage($chat->contact_phone, $this->replyBody);

        if ($success) {
            $timestamp = now();
            \App\Models\WaMessage::create([
                'wa_chat_id' => $chat->id,
                'wa_message_id' => 'out_' . uniqid(),
                'direction' => 'outbound',
                'type' => 'text',
                'body' => $this->replyBody,
                'status' => 'sent',
                'sent_at' => $timestamp,
            ]);

            $chat->update([
                'last_message_at' => $timestamp,
                'last_message_body' => 'You: ' . mb_substr($this->replyBody, 0, 240),
            ]);

            $this->replyBody = '';
            $this->loadMessages();
            $this->loadChats();
        }
    }

    public function closeChat()
    {
        if (!$this->activeChatId) return;

        \App\Models\WaChat::where('id', $this->activeChatId)->update(['status' => 'closed']);
        $this->activeChatId = null;
        $this->messages = [];
        $this->loadChats();
    }
}
