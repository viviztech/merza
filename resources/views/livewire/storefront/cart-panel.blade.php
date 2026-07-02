<div class="max-w-3xl mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-8">
        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 text-white flex items-center justify-center shadow-md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
        </div>
        <div>
            <h1 class="text-2xl font-extrabold text-stone-900">Your Cart</h1>
            @if($items->isNotEmpty())
                <p class="text-xs text-stone-400">{{ $items->count() }} {{ Str::plural('item', $items->count()) }} in your cart</p>
            @endif
        </div>
    </div>

    @if($items->isEmpty())
        {{-- Empty state --}}
        <div class="text-center py-20">
            <div class="w-24 h-24 rounded-3xl bg-amber-50 border-2 border-amber-100 flex items-center justify-center text-5xl mx-auto mb-5">🛒</div>
            <h3 class="text-xl font-extrabold text-stone-700 mb-2">Your cart is empty</h3>
            <p class="text-stone-400 text-sm mb-6 max-w-xs mx-auto">Looks like you haven't added any fruits yet. Browse our fresh selection!</p>
            <a href="{{ route('products.index') }}"
               class="inline-flex items-center gap-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-extrabold px-8 py-4 rounded-2xl hover:from-amber-400 hover:to-orange-400 transition-all shadow-lg hover:-translate-y-0.5">
                Browse Fruits
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    @else
        <div class="grid lg:grid-cols-5 gap-6">

            {{-- Cart items --}}
            <div class="lg:col-span-3 space-y-3">
                @foreach($items as $item)
                    <div class="bg-white rounded-3xl border border-amber-100 p-4 flex gap-4 items-center shadow-sm hover:shadow-md transition-shadow">

                        {{-- Thumbnail --}}
                        <div class="w-18 h-18 rounded-2xl flex-shrink-0 overflow-hidden border border-amber-100 shadow-sm"
                             style="width:72px; height:72px; background: linear-gradient(145deg, #fef9c3, #fef3c7);">
                            @if($item->thumbnail_url && !str_contains($item->thumbnail_url, 'placeholder'))
                                <img src="{{ $item->thumbnail_url }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-3xl">🥭</div>
                            @endif
                        </div>

                        {{-- Details --}}
                        <div class="flex-1 min-w-0">
                            <h4 class="font-extrabold text-sm text-stone-800 truncate">{{ $item->product_name }}</h4>
                            <p class="text-xs text-stone-400 mt-0.5">{{ $item->variant_name }}</p>
                            <p class="text-amber-600 font-extrabold text-sm mt-1">RM{{ number_format($item->price, 2) }} each</p>
                        </div>

                        {{-- Qty stepper --}}
                        <div class="flex items-center gap-0 bg-stone-100 rounded-xl overflow-hidden border border-stone-200 flex-shrink-0">
                            <button wire:click="updateQty({{ $item->variant_id }}, {{ $item->qty - 1 }})"
                                    class="w-8 h-8 flex items-center justify-center text-stone-500 hover:bg-red-100 hover:text-red-600 transition-colors font-extrabold">−</button>
                            <span class="w-7 text-center text-sm font-extrabold text-stone-800">{{ $item->qty }}</span>
                            <button wire:click="updateQty({{ $item->variant_id }}, {{ $item->qty + 1 }})"
                                    class="w-8 h-8 flex items-center justify-center text-stone-500 hover:bg-emerald-100 hover:text-emerald-600 transition-colors font-extrabold">+</button>
                        </div>

                        {{-- Line total + remove --}}
                        <div class="text-right flex-shrink-0">
                            <p class="font-extrabold text-sm text-stone-800">RM{{ number_format($item->line_total, 2) }}</p>
                            <button wire:click="remove({{ $item->variant_id }})"
                                    class="text-[10px] text-red-400 hover:text-red-600 mt-1 transition-colors font-medium">
                                Remove
                            </button>
                        </div>
                    </div>
                @endforeach

                <a href="{{ route('products.index') }}"
                   class="flex items-center gap-2 text-sm text-stone-400 hover:text-amber-600 transition-colors mt-2 font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Continue Shopping
                </a>
            </div>

            {{-- Order summary --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-3xl border border-amber-100 shadow-sm overflow-hidden sticky top-24">

                    <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-b border-amber-100 px-5 py-4">
                        <h2 class="font-extrabold text-stone-800">Order Summary</h2>
                    </div>

                    <div class="p-5 space-y-3">

                        {{-- Free delivery progress --}}
                        @php $freeAt = 150; $progress = min(100, ($subtotal / $freeAt) * 100); @endphp
                        @if($deliveryFee > 0)
                            <div class="bg-amber-50 border border-amber-100 rounded-2xl p-3 mb-1">
                                <p class="text-xs font-bold text-amber-700 mb-2">
                                    🚚 Add RM{{ number_format($freeAt - $subtotal, 2) }} more for FREE delivery!
                                </p>
                                <div class="bg-amber-100 rounded-full h-2 overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-amber-500 to-orange-500 rounded-full transition-all duration-500"
                                         style="width: {{ $progress }}%"></div>
                                </div>
                            </div>
                        @else
                            <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-3 mb-1 flex items-center gap-2">
                                <span class="text-emerald-600">✓</span>
                                <p class="text-xs font-bold text-emerald-700">You've unlocked FREE delivery!</p>
                            </div>
                        @endif

                        {{-- Line items --}}
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between text-stone-500">
                                <span>Subtotal</span>
                                <span class="font-semibold text-stone-700">RM{{ number_format($subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-stone-500">
                                <span>Delivery</span>
                                <span class="{{ $deliveryFee === 0 ? 'text-emerald-600 font-bold' : 'font-semibold text-stone-700' }}">
                                    {{ $deliveryFee === 0 ? '🎉 FREE' : 'RM' . number_format($deliveryFee, 2) }}
                                </span>
                            </div>
                        </div>

                        <div class="border-t border-amber-100 pt-3">
                            <div class="flex justify-between items-center">
                                <span class="font-extrabold text-stone-800">Total</span>
                                <span class="text-2xl font-extrabold text-amber-600">RM{{ number_format($total, 2) }}</span>
                            </div>
                        </div>

                        <a href="{{ route('checkout.index') }}"
                           class="block w-full text-center bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-white font-extrabold py-4 rounded-2xl transition-all shadow-lg shadow-amber-200/50 hover:shadow-xl hover:-translate-y-0.5 text-base mt-2">
                            Checkout Now →
                        </a>

                        <div class="flex items-center justify-center gap-3 pt-1">
                            <span class="text-[10px] text-stone-400">🔒 Secure</span>
                            <span class="text-[10px] text-stone-400">📦 Packed Fresh</span>
                            <span class="text-[10px] text-stone-400">🚚 Fast Shipping</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
