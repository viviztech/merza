@props(['order'])

@php
    $statusColors = [
        'pending'    => 'bg-yellow-100 text-yellow-700 border-yellow-200',
        'confirmed'  => 'bg-blue-100 text-blue-700 border-blue-200',
        'preparing'  => 'bg-purple-100 text-purple-700 border-purple-200',
        'delivering' => 'bg-orange-100 text-orange-700 border-orange-200',
        'delivered'  => 'bg-green-100 text-green-700 border-green-200',
        'cancelled'  => 'bg-red-100 text-red-700 border-red-200',
    ];
    $colorClass = $statusColors[$order->status] ?? 'bg-stone-100 text-stone-600 border-stone-200';

    $statusSteps = ['pending', 'confirmed', 'preparing', 'delivering', 'delivered'];
    $currentStep = array_search($order->status, $statusSteps);
@endphp

<div {{ $attributes->merge(['class' => 'bg-white border border-stone-100 rounded-2xl p-6']) }}>
    <div class="flex items-center justify-between mb-4">
        <span class="text-sm font-semibold text-stone-500">Status</span>
        <span class="inline-flex items-center gap-1.5 text-sm font-bold px-3 py-1 rounded-full border {{ $colorClass }}">
            {{ ucfirst($order->status) }}
        </span>
    </div>

    @if($order->status !== 'cancelled')
        <div class="flex items-center gap-1 mt-2">
            @foreach($statusSteps as $i => $step)
                <div class="flex-1 h-1.5 rounded-full {{ $currentStep !== false && $i <= $currentStep ? 'bg-amber-400' : 'bg-stone-100' }} transition-all"></div>
            @endforeach
        </div>
        <div class="flex justify-between mt-1.5">
            @foreach($statusSteps as $step)
                <span class="text-xs text-stone-400 capitalize">{{ $step }}</span>
            @endforeach
        </div>
    @endif

    @if($order->tracking_number)
        <div class="mt-4 pt-4 border-t border-stone-100">
            <p class="text-xs text-stone-500">Tracking number</p>
            <p class="text-sm font-mono font-bold text-stone-700 mt-0.5">{{ $order->tracking_number }}</p>
        </div>
    @endif
</div>
