<x-filament-panels::page>
    {{-- Contact details, active enquiry, orders, CRM stats (standard Filament infolist) --}}
    {{ $this->infolist }}

    @if ($this->record->active_lead)
        <div class="flex justify-end -mt-2">
            <x-filament::button
                type="button"
                color="gray"
                size="sm"
                wire:click="advanceLeadStage"
            >
                Advance Stage →
            </x-filament::button>
        </div>
    @endif

    {{-- Chat Thread + inline reply composer --}}
    <x-filament::section>
        <x-slot name="heading">
            Chat Thread — {{ $this->record->name }} ({{ $this->record->phone }})
        </x-slot>

        @include('filament.components.conversation-thread', [
            'contactId' => $this->record->id,
            'channel'   => 'whatsapp',
        ])

        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-white/10 space-y-2">
            <textarea
                wire:model="replyMessage"
                rows="3"
                placeholder="Type a WhatsApp reply…"
                class="fi-input block w-full rounded-lg border-none bg-white text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20 px-3 py-2"
            ></textarea>

            <div class="flex items-center justify-end gap-2">
                <x-filament::button
                    type="button"
                    color="warning"
                    outlined
                    icon="heroicon-o-sparkles"
                    wire:click="generateAiDraft"
                >
                    AI Draft
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="success"
                    icon="heroicon-o-paper-airplane"
                    wire:click="sendReply"
                >
                    Send
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
