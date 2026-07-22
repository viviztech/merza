<x-layouts.storefront title="Order #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}">
    <div class="max-w-2xl mx-auto px-4 py-10">

        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('account.orders') }}" class="text-stone-400 hover:text-stone-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 class="text-2xl font-extrabold text-stone-900">
                Order #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
            </h1>
        </div>

        <x-order-status-timeline :order="$order" class="mb-5" />

        {{-- Order Items --}}
        <div class="bg-white border border-stone-100 rounded-2xl p-6 mb-5">
            <h2 class="text-base font-bold text-stone-800 mb-4">Items Ordered</h2>
            <div class="divide-y divide-stone-50">
                @foreach($order->items as $item)
                    <div class="flex items-center justify-between py-3 first:pt-0 last:pb-0">
                        <div>
                            <p class="text-sm font-semibold text-stone-800">{{ $item->product_name ?? 'Product' }}</p>
                            <p class="text-xs text-stone-400 mt-0.5">Qty: {{ $item->quantity }}</p>
                        </div>
                        <p class="text-sm font-bold text-stone-800">₹{{ number_format($item->subtotal, 2) }}</p>
                    </div>
                @endforeach
            </div>
            <div class="border-t border-stone-100 mt-4 pt-4 flex justify-between items-center">
                <span class="text-sm font-bold text-stone-800">Total</span>
                <span class="text-lg font-extrabold text-amber-600">₹{{ number_format($order->total, 2) }}</span>
            </div>
        </div>

        {{-- Order Meta --}}
        <div class="bg-white border border-stone-100 rounded-2xl p-6 mb-5">
            <h2 class="text-base font-bold text-stone-800 mb-4">Order Details</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-stone-500">Placed on</dt>
                    <dd class="font-semibold text-stone-800">{{ $order->created_at->format('d M Y, h:i A') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-stone-500">Payment status</dt>
                    <dd>
                        @if($order->payment_status === 'paid')
                            <span class="inline-flex items-center gap-1 text-green-700 font-semibold">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Paid
                            </span>
                        @else
                            <span class="text-yellow-600 font-semibold capitalize">{{ $order->payment_status ?? 'Pending' }}</span>
                        @endif
                    </dd>
                </div>
                @if($order->notes)
                    <div class="flex justify-between">
                        <dt class="text-stone-500">Notes</dt>
                        <dd class="font-semibold text-stone-800 text-right max-w-xs">{{ $order->notes }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- Help --}}
        <div class="bg-amber-50 border border-amber-100 rounded-2xl p-5 flex items-center justify-between">
            <div>
                <p class="text-sm font-bold text-stone-800">Need help with this order?</p>
                <p class="text-xs text-stone-500 mt-0.5">We're available on WhatsApp</p>
            </div>
            <a href="https://wa.me/919360064278?text=Hi%2C+I+need+help+with+order+%23{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}"
               target="_blank"
               class="flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white text-sm font-bold px-4 py-2.5 rounded-xl transition-all">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                Chat with us
            </a>
        </div>
    </div>
</x-layouts.storefront>
