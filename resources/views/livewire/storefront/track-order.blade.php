<div class="max-w-2xl mx-auto px-4 py-10">

    <h1 class="text-2xl font-extrabold text-stone-900 mb-2">Track Your Order</h1>
    <p class="text-stone-500 text-sm mb-8">Enter your order number and the phone number you ordered with.</p>

    @if($order)
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-bold text-stone-800">Order #{{ $order->order_number }}</h2>
            <button wire:click="searchAgain" class="text-sm font-semibold text-amber-600 hover:text-amber-700">
                Track another order
            </button>
        </div>

        <x-order-status-timeline :order="$order" class="mb-5" />

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

        <div class="bg-amber-50 border border-amber-100 rounded-2xl p-5 flex items-center justify-between">
            <div>
                <p class="text-sm font-bold text-stone-800">Need help with this order?</p>
                <p class="text-xs text-stone-500 mt-0.5">We're available on WhatsApp</p>
            </div>
            <a href="https://wa.me/919360064278?text=Hi%2C+I+need+help+with+order+{{ $order->order_number }}"
               target="_blank"
               class="flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white text-sm font-bold px-4 py-2.5 rounded-xl transition-all">
                Chat with us
            </a>
        </div>

    @else
        <form wire:submit="find" class="bg-white border border-stone-100 rounded-2xl p-6 space-y-4">
            <div>
                <label class="block text-sm font-semibold text-stone-700 mb-1.5">Order Number</label>
                <input type="text" wire:model="orderNumber" placeholder="e.g. MRZ-AB12CD"
                       class="w-full rounded-xl border-stone-200 focus:border-amber-400 focus:ring-amber-400 text-sm">
                @error('orderNumber') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-stone-700 mb-1.5">Phone Number</label>
                <input type="tel" wire:model="phone" placeholder="Phone number used at checkout"
                       class="w-full rounded-xl border-stone-200 focus:border-amber-400 focus:ring-amber-400 text-sm">
                @error('phone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            @if($searched && !$order)
                <div class="bg-red-50 border border-red-100 rounded-xl p-3 text-sm text-red-600">
                    We couldn't find an order matching that number and phone. Double-check both, or
                    <a href="https://wa.me/919360064278" target="_blank" class="font-bold underline">message us on WhatsApp</a>.
                </div>
            @endif

            <button type="submit" wire:loading.attr="disabled"
                    class="w-full bg-amber-500 hover:bg-amber-600 text-white font-extrabold py-3 rounded-xl transition-all">
                Track Order
            </button>
        </form>
    @endif
</div>
