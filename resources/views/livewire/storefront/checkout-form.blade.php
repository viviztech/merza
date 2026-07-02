<div class="max-w-5xl mx-auto px-4 py-8">

    @if($orderPlaced)
        {{-- Success screen --}}
        <div class="text-center py-16">
            <div class="text-7xl mb-4">✅</div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Order Placed!</h1>
            <p class="text-gray-500 mb-1">Your order number is</p>
            <p class="text-3xl font-bold text-green-700 mb-6">{{ $orderNumber }}</p>
            <p class="text-gray-500 text-sm max-w-md mx-auto mb-8">
                We'll contact you on WhatsApp to confirm delivery details. Thank you for shopping with Merza!
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('home') }}"
                   class="bg-green-700 text-white font-bold px-8 py-3 rounded-xl hover:bg-green-600 transition-colors">
                    Back to Home
                </a>
                <a href="https://wa.me/60123456789?text=Hi%2C+my+order+number+is+{{ $orderNumber }}"
                   target="_blank"
                   class="border-2 border-green-700 text-green-700 font-bold px-8 py-3 rounded-xl hover:bg-green-50 transition-colors">
                    Track on WhatsApp
                </a>
            </div>
        </div>
    @else
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Checkout</h1>

        @if($items->isEmpty())
            <div class="text-center py-16">
                <p class="text-gray-500 text-lg">Your cart is empty.</p>
                <a href="{{ route('products.index') }}" class="mt-4 inline-block text-green-700 underline">Shop now</a>
            </div>
        @else
            @error('cart') <p class="text-red-500 text-sm mb-4">{{ $message }}</p> @enderror

            <div class="grid lg:grid-cols-3 gap-6">

                {{-- Form --}}
                <form wire:submit="placeOrder" class="lg:col-span-2 space-y-5">

                    <div class="bg-white rounded-2xl border border-gray-100 p-5">
                        <h2 class="font-bold text-gray-800 mb-4">Delivery Information</h2>
                        <div class="grid sm:grid-cols-2 gap-4">

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                                <input wire:model="customer_name" type="text" placeholder="Ahmad bin Ali"
                                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 @error('customer_name') border-red-400 @enderror">
                                @error('customer_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                                <input wire:model="customer_phone" type="tel" placeholder="012-3456789"
                                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 @error('customer_phone') border-red-400 @enderror">
                                @error('customer_phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email (optional)</label>
                                <input wire:model="customer_email" type="email" placeholder="email@example.com"
                                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Address *</label>
                                <textarea wire:model="delivery_address" rows="2" placeholder="No. 12, Jalan..."
                                          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 @error('delivery_address') border-red-400 @enderror"></textarea>
                                @error('delivery_address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                                <input wire:model="city" type="text" placeholder="Kuala Lumpur"
                                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 @error('city') border-red-400 @enderror">
                                @error('city') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Postcode *</label>
                                <input wire:model="postcode" type="text" placeholder="50000"
                                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 @error('postcode') border-red-400 @enderror">
                                @error('postcode') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">State *</label>
                                <select wire:model="state"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 @error('state') border-red-400 @enderror">
                                    <option value="">Select state…</option>
                                    @foreach($states as $s)
                                        <option value="{{ $s }}">{{ $s }}</option>
                                    @endforeach
                                </select>
                                @error('state') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-gray-100 p-5">
                        <h2 class="font-bold text-gray-800 mb-4">Payment Method</h2>
                        <div class="space-y-2">
                            @foreach([
                                ['cod', '💵', 'Cash on Delivery', 'Pay when your order arrives'],
                                ['bank_transfer', '🏦', 'Bank Transfer', 'We\'ll send account details via WhatsApp'],
                                ['whatsapp', '💬', 'WhatsApp Order', 'Confirm & pay via WhatsApp'],
                            ] as [$val, $icon, $label, $desc])
                                <label class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-colors
                                              {{ $payment_method === $val ? 'border-green-600 bg-green-50' : 'border-gray-100 hover:border-gray-200' }}">
                                    <input wire:model="payment_method" type="radio" value="{{ $val }}" class="sr-only">
                                    <span class="text-xl">{{ $icon }}</span>
                                    <div>
                                        <p class="font-medium text-sm text-gray-800">{{ $label }}</p>
                                        <p class="text-xs text-gray-400">{{ $desc }}</p>
                                    </div>
                                    @if($payment_method === $val)
                                        <svg class="ml-auto w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-gray-100 p-5">
                        <h2 class="font-bold text-gray-800 mb-3">Order Notes (optional)</h2>
                        <textarea wire:model="notes" rows="2" placeholder="Any special instructions…"
                                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                    </div>

                    <button type="submit"
                            wire:loading.attr="disabled"
                            class="w-full bg-green-700 hover:bg-green-600 disabled:opacity-60 text-white font-bold py-4 rounded-xl transition-colors text-base">
                        <span wire:loading.remove wire:target="placeOrder">Place Order</span>
                        <span wire:loading wire:target="placeOrder">Placing order…</span>
                    </button>
                </form>

                {{-- Order summary sidebar --}}
                <div class="space-y-4">
                    <div class="bg-white rounded-2xl border border-gray-100 p-5 sticky top-24">
                        <h2 class="font-bold text-gray-800 mb-4">Order Summary</h2>
                        <div class="space-y-3 mb-4">
                            @foreach($items as $item)
                                <div class="flex gap-3 items-center">
                                    <div class="w-10 h-10 rounded-lg bg-green-50 flex-shrink-0 flex items-center justify-center text-lg">🥭</div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-gray-800 truncate">{{ $item->product_name }}</p>
                                        <p class="text-xs text-gray-400">{{ $item->variant_name }} × {{ $item->qty }}</p>
                                    </div>
                                    <p class="text-xs font-bold text-gray-800">RM{{ number_format($item->line_total, 2) }}</p>
                                </div>
                            @endforeach
                        </div>
                        <div class="border-t pt-3 space-y-1 text-sm">
                            <div class="flex justify-between text-gray-500">
                                <span>Subtotal</span>
                                <span>RM{{ number_format($subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-500">
                                <span>Delivery</span>
                                <span>{{ $deliveryFee == 0 ? 'FREE' : 'RM' . number_format($deliveryFee, 2) }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-gray-900 pt-1 border-t">
                                <span>Total</span>
                                <span class="text-green-700">RM{{ number_format($total, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('cart.index') }}"
                       class="block text-center text-sm text-gray-400 hover:text-green-700 transition-colors">
                        ← Edit Cart
                    </a>
                </div>
            </div>
        @endif
    @endif
</div>
