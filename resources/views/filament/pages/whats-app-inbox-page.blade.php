<x-filament-panels::page>
    <div wire:poll.5s="loadChats" class="flex h-[calc(100vh-12rem)] bg-white dark:bg-gray-900 rounded-xl overflow-hidden shadow-sm border border-gray-200 dark:border-gray-800">
        
        {{-- Left Sidebar: Chat List --}}
        <div class="w-1/3 border-r border-gray-200 dark:border-gray-800 flex flex-col">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Chats</h2>
            </div>
            
            <div class="flex-1 overflow-y-auto">
                @forelse($chats as $chat)
                    <div 
                        wire:click="selectChat({{ $chat->id }})"
                        class="p-4 cursor-pointer border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors {{ $activeChatId === $chat->id ? 'bg-primary-50 dark:bg-primary-900/10' : '' }}"
                    >
                        <div class="flex justify-between items-start mb-1">
                            <h3 class="font-medium text-gray-900 dark:text-white truncate">
                                {{ $chat->contact_name ?? $chat->contact_phone }}
                            </h3>
                            @if($chat->last_message_at)
                                <span class="text-xs text-gray-500 whitespace-nowrap ml-2">
                                    {{ $chat->last_message_at->shortAbsoluteDiffForHumans() }}
                                </span>
                            @endif
                        </div>
                        
                        @if($chat->tenant)
                            <div class="text-xs text-primary-600 dark:text-primary-400 mb-1">
                                Tenant: {{ $chat->tenant->full_name }}
                            </div>
                        @endif
                        
                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                            {{ $chat->last_message_body ?: 'No messages yet' }}
                        </p>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500">
                        No conversations yet.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Right Content: Active Chat --}}
        <div class="flex-1 flex flex-col bg-gray-50/50 dark:bg-gray-900/50 relative">
            @if($activeChatId)
                @php $activeChat = collect($chats)->firstWhere('id', $activeChatId); @endphp
                
                {{-- Chat Header --}}
                <div class="p-4 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center z-10">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $activeChat->contact_name ?? $activeChat->contact_phone }}
                        </h2>
                        <p class="text-sm text-gray-500">{{ $activeChat->contact_phone }}</p>
                    </div>
                    
                    <button wire:click="closeChat" class="text-sm text-danger-600 hover:text-danger-500 font-medium">
                        Close Chat
                    </button>
                </div>

                {{-- Messages Area --}}
                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    @forelse($messages as $msg)
                        <div class="flex w-full {{ $msg->direction === 'outbound' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[75%] rounded-2xl px-4 py-2 shadow-sm {{ $msg->direction === 'outbound' ? 'bg-primary-600 text-white rounded-br-none' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-bl-none border border-gray-200 dark:border-gray-700' }}">
                                @if($msg->type !== 'text')
                                    <div class="text-xs font-semibold mb-1 opacity-75">[{{ strtoupper($msg->type) }}]</div>
                                @endif
                                
                                <p class="text-sm whitespace-pre-wrap">{{ $msg->body }}</p>
                                
                                <div class="text-[10px] text-right mt-1 opacity-70">
                                    {{ $msg->sent_at?->format('H:i') }}
                                    @if($msg->direction === 'outbound')
                                        · {{ ucfirst($msg->status) }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex h-full items-center justify-center text-gray-500">
                            Start the conversation by replying below.
                        </div>
                    @endforelse
                </div>

                {{-- Reply Box --}}
                <div class="p-4 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 z-10">
                    <form wire:submit.prevent="sendReply" class="flex gap-2">
                        <x-filament::input.wrapper class="flex-1">
                            <x-filament::input
                                type="text"
                                wire:model="replyBody"
                                placeholder="Type a message (24h window limit)..."
                                class="w-full"
                            />
                        </x-filament::input.wrapper>
                        
                        <x-filament::button type="submit" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="sendReply">Send</span>
                            <span wire:loading wire:target="sendReply">Sending...</span>
                        </x-filament::button>
                    </form>
                </div>

            @else
                <div class="flex-1 flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-chat-bubble-left-right class="w-16 h-16 text-gray-300 dark:text-gray-700 mb-4" />
                    <p class="text-lg font-medium">Select a chat to view messages</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
