{{-- Reusable chat-thread partial. Include with:
     @include('filament.components.conversation-thread', ['contactId' => $id, 'channel' => 'whatsapp', 'currentMessageId' => null]) --}}
@php
    $channel          = $channel ?? 'whatsapp';
    $currentMessageId = $currentMessageId ?? null;

    $thread = \App\Models\Conversation::where('contact_id', $contactId)
        ->where('channel', $channel)
        ->orderBy('created_at', 'asc')
        ->get();
    $contact = \App\Models\Contact::find($contactId);
@endphp

@if ($thread->isEmpty())
    <p class="text-sm text-gray-400">No messages yet.</p>
@else
    <div class="space-y-3 max-h-[500px] overflow-y-auto p-2">
        @foreach ($thread as $msg)
            @php
                $isOutbound = $msg->direction === 'outbound';
                $isDraft    = $msg->is_bot && is_null($msg->sent_at);
                $isCurrent  = $currentMessageId && $msg->id === $currentMessageId;
            @endphp

            <div class="flex {{ $isOutbound ? 'justify-end' : 'justify-start' }}">
                <div
                    class="max-w-[72%] rounded-2xl px-4 py-2 text-sm shadow-sm
                        {{ $isOutbound
                            ? ($isDraft ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-900 dark:text-yellow-200 border border-yellow-300 dark:border-yellow-700' : 'bg-green-600 text-white')
                            : 'bg-white dark:bg-white/10 border border-gray-200 dark:border-white/10 text-gray-800 dark:text-gray-100'
                        }}
                        {{ $isCurrent ? 'ring-2 ring-primary-500' : '' }}"
                >
                    {{-- Labels --}}
                    <div class="flex items-center gap-2 mb-1">
                        @if ($isOutbound)
                            <span class="text-xs font-semibold opacity-70">
                                {{ $msg->is_bot ? '🤖 Bot' : '👤 Agent' }}
                            </span>
                            @if ($isDraft)
                                <span class="text-xs font-bold uppercase tracking-wide text-yellow-700 dark:text-yellow-400">
                                    DRAFT
                                </span>
                            @endif
                        @else
                            <span class="text-xs font-semibold opacity-70">
                                {{ $contact?->name }}
                            </span>
                        @endif
                    </div>

                    {{-- Message body --}}
                    <p class="whitespace-pre-wrap leading-snug">{{ $msg->message }}</p>

                    {{-- Timestamp + status --}}
                    <div class="flex items-center gap-1 mt-1 justify-end opacity-60 text-xs">
                        {{ $msg->sent_at?->format('d M, h:i A') ?? 'Unsent' }}
                        @if ($isOutbound && !$isDraft)
                            @if ($msg->status === 'read') ✓✓
                            @elseif ($msg->status === 'delivered') ✓✓
                            @else ✓
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
