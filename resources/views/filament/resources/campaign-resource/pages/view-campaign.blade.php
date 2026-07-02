<x-filament-panels::page>
    {{-- Infolist --}}
    {{ $this->infolist }}

    {{-- Campaign Message preview --}}
    @if ($this->record->message)
        <x-filament::section>
            <x-slot name="heading">Message Template</x-slot>
            <p class="text-sm whitespace-pre-wrap leading-relaxed text-gray-700 dark:text-gray-300">{{ $this->record->message }}</p>
        </x-filament::section>
    @endif

    {{-- Drip steps --}}
    @if ($this->record->type === 'drip' && $this->record->steps->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">Drip Sequence ({{ $this->record->steps->count() }} steps)</x-slot>
            <div class="space-y-3">
                @foreach ($this->record->steps as $step)
                    <div class="rounded-lg border border-gray-200 dark:border-white/10 p-3">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 px-2 py-0.5 rounded">
                                Step {{ $step->step_number }}
                            </span>
                            <span class="text-xs text-gray-500">
                                {{ $step->delay_days === 0 ? 'Immediately' : "After {$step->delay_days} day(s)" }}
                            </span>
                        </div>
                        <p class="text-sm whitespace-pre-wrap text-gray-700 dark:text-gray-300">{{ $step->message }}</p>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    {{-- Recipients table --}}
    <x-filament::section>
        <x-slot name="heading">
            Recipients
            <span class="ml-2 text-xs font-normal text-gray-500">
                {{ $this->record->sent_count }}/{{ $this->record->total_contacts }} sent
                @if ($this->record->total_contacts > 0)
                    &mdash; {{ $this->record->successRate() }}% success
                @endif
            </span>
        </x-slot>

        @php
            $recipients = $this->record->contacts()->with('contact')->latest()->paginate(20);
        @endphp

        @if ($recipients->isEmpty())
            <p class="text-sm text-gray-400">No recipients enrolled yet. Launch the campaign to enroll contacts.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10 text-left text-xs text-gray-500 uppercase tracking-wide">
                            <th class="pb-2 pr-4">Contact</th>
                            <th class="pb-2 pr-4">Phone</th>
                            <th class="pb-2 pr-4">Status</th>
                            <th class="pb-2 pr-4">Step</th>
                            <th class="pb-2 pr-4">Last Sent</th>
                            <th class="pb-2">Next Send</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($recipients as $cc)
                            <tr>
                                <td class="py-2 pr-4 font-medium">{{ $cc->contact?->name ?? '—' }}</td>
                                <td class="py-2 pr-4 text-gray-500">{{ $cc->contact?->phone ?? '—' }}</td>
                                <td class="py-2 pr-4">
                                    @php
                                        $color = match ($cc->status) {
                                            'sent'          => 'text-green-600 dark:text-green-400',
                                            'failed'        => 'text-red-600 dark:text-red-400',
                                            'replied'       => 'text-blue-600 dark:text-blue-400',
                                            'unsubscribed'  => 'text-gray-400',
                                            default         => 'text-yellow-600 dark:text-yellow-400',
                                        };
                                    @endphp
                                    <span class="text-xs font-semibold {{ $color }} uppercase">{{ $cc->status }}</span>
                                </td>
                                <td class="py-2 pr-4 text-gray-500">{{ $cc->current_step ?: '—' }}</td>
                                <td class="py-2 pr-4 text-gray-500">
                                    {{ $cc->last_sent_at?->format('d M, h:i A') ?? '—' }}
                                </td>
                                <td class="py-2 text-gray-500">
                                    {{ $cc->next_send_at?->format('d M, h:i A') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($recipients->hasPages())
                    <div class="mt-4">{{ $recipients->links() }}</div>
                @endif
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
