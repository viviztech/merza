<div class="max-w-3xl mx-auto px-4 py-8">

    <h1 class="text-2xl font-bold text-gray-900 mb-6">Your Cart</h1>

    @if($items->isEmpty())
        <div class="text-center py-20">
            <div class="text-6xl mb-4">🛒</div>
            <p class="text-gray-500 text-lg font-medium">Your cart is empty</p>
            <a href="{{ route('products.index') }}"
               class="mt-4 inline-block bg-green-700 text-white font-bold px-6 py-3 rounded-xl hover:bg-green-600 transition-colors">
                Browse Products
            </a>
        </div>
    @else
        <div class="space-y-3 mb-6">
            @foreach($items as $item)
                <div class="bg-white rounded-2xl border border-gray-100 p-4 flex gap-4 items-center">

                    {{-- Thumbnail --}}
                    <div class="w-16 h-16 rounded-xl bg-green-50 flex-shrink-0 overflow-hidden">
                        @if($item->thumbnail_url && !str_contains($item->thumbnail_url, 'placeholder'))
                            <img src="{{ $item->thumbnail_url }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-3xl">🥭</div>
                        @endif
                    </div>

                    {{-- Details --}}
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm text-gray-800 truncate">{{ $item->product_name }}</p>
                        <p class="text-xs text-gray-400">{{ $item->variant_name }}</p>
                        <p class="text-green-700 font-bold text-sm mt-0.5">RM{{ number_format($item->price, 2) }}</p>
                    </div>

                    {{-- Qty stepper --}}
                    <div class="flex items-center gap-2">
                        <button wire:click="updateQty({{ $item->variant_id }}, {{ $item->qty - 1 }})"
                                class="w-7 h-7 rounded-full border border-gray-200 flex items-center justify-center text-gray-600 hover:border-red-400 hover:text-red-500 transition-colors text-sm font-bold">−</button>
                        <span class="w-6 text-center text-sm font-semibold">{{ $item->qty }}</span>
                        <button wire:click="updateQty({{ $item->variant_id }}, {{ $item->qty + 1 }})"
                                class="w-7 h-7 rounded-full border border-gray-200 flex items-center justify-center text-gray-600 hover:border-green-500 transition-colors text-sm font-bold">+</button>
                    </div>

                    {{-- Line total --}}
                    <div class="text-right min-w-[4rem]">
                        <p class="font-bold text-sm text-gray-800">RM{{ number_format($item->line_total, 2) }}</p>
                        <button wire:click="remove({{ $item->variant_id }})"
                                class="text-xs text-red-400 hover:text-red-600 mt-1">Remove</button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Order summary --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <h2 class="font-bold text-gray-800 mb-3">Order Summary</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span>RM{{ number_format($subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Delivery</span>
                    <span>
                        @if($deliveryFee === 0)
                            <span class="text-green-600 font-medium">FREE</span>
                        @else
                            RM{{ number_format($deliveryFee, 2) }}
                        @endif
                    </span>
                </div>
                @if($deliveryFee > 0)
                    <p class="text-xs text-gray-400">Free delivery on orders RM150+</p>
                @endif
                <div class="border-t pt-2 flex justify-between font-bold text-gray-900 text-base">
                    <span>Total</span>
                    <span class="text-green-700">RM{{ number_format($total, 2) }}</span>
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-3">
                <a href="{{ route('checkout.index') }}"
                   class="w-full bg-green-700 hover:bg-green-600 text-white font-bold py-3 rounded-xl text-center transition-colors">
                    Proceed to Checkout
                </a>
                <a href="{{ route('products.index') }}"
                   class="w-full text-center text-sm text-gray-500 hover:text-green-700 transition-colors">
                    ← Continue Shopping
                </a>
            </div>
        </div>
    @endif
</div>
