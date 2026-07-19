<x-filament-panels::page>
    {{-- Infolist (standard Filament fields) --}}
    {{ $this->infolist }}

    {{-- Chat Thread --}}
    <x-filament::section>
        <x-slot name="heading">
            Chat Thread —
            {{ $this->record->contact?->name }}
            ({{ $this->record->contact?->phone }})
        </x-slot>

        @include('filament.components.conversation-thread', [
            'contactId'        => $this->record->contact_id,
            'channel'          => $this->record->channel,
            'currentMessageId' => $this->record->id,
        ])
    </x-filament::section>
</x-filament-panels::page>
