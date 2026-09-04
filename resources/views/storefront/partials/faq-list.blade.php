@php
    $faqItems = \App\Support\FaqData::items();

    $itemsToShow = isset($limit) ? array_slice($faqItems, 0, $limit) : $faqItems;
@endphp

<div class="space-y-3">
    @foreach($itemsToShow as [$q, $a])
        <details class="group bg-amber-50 border border-amber-100 rounded-2xl p-5">
            <summary class="flex items-center justify-between cursor-pointer font-extrabold text-sm text-stone-800 list-none">
                {{ $q }}
                <svg class="w-4 h-4 text-amber-500 transition-transform group-open:rotate-45" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
            </summary>
            <p class="text-sm text-stone-600 mt-3 leading-relaxed">{{ $a }}</p>
        </details>
    @endforeach
</div>
