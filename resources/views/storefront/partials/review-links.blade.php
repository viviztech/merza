@if($order->status === 'delivered')
    @php
        $reviewProducts = $order->items
            ->map(fn ($item) => $item->variant?->product)
            ->filter(fn ($product) => $product?->is_active)
            ->unique('id');
    @endphp
    @if($reviewProducts->isNotEmpty())
        <section class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
            <h2 class="font-bold text-emerald-950">How was your order?</h2>
            <p class="mt-1 text-sm text-stone-600">Your feedback helps other shoppers choose confidently.</p>
            <div class="mt-3 flex flex-wrap gap-3">
                @foreach($reviewProducts as $product)
                    <a href="{{ route('products.show', $product->slug) }}#customer-reviews" class="text-sm font-bold text-emerald-800 underline">Review {{ $product->name }}</a>
                @endforeach
            </div>
        </section>
    @endif
@endif
