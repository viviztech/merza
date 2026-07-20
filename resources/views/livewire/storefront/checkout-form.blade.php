<div class="max-w-5xl mx-auto px-4 py-8">

    @if($orderPlaced)
        {{-- ══════════════════════════════════════ --}}
        {{-- SUCCESS SCREEN --}}
        {{-- ══════════════════════════════════════ --}}
        <div class="text-center py-12 max-w-lg mx-auto">
            <div class="relative inline-flex mb-6">
                <div class="w-28 h-28 rounded-3xl bg-gradient-to-br from-emerald-400 to-green-600 flex items-center justify-center text-6xl shadow-2xl shadow-emerald-200">
                    🎉
                </div>
                <span class="absolute -top-2 -right-2 w-10 h-10 rounded-full bg-white border-4 border-emerald-500 flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
            </div>

            <h1 class="text-3xl font-extrabold text-brand-green-dark mb-2">Order Placed! 🥭</h1>
            <p class="text-stone-500 mb-4">Your fresh fruits are being prepared.</p>

            <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-200 rounded-3xl p-5 mb-6">
                <p class="text-xs text-amber-600 font-bold uppercase tracking-widest mb-1">Order Number</p>
                <p class="text-3xl font-extrabold text-amber-600">{{ $orderNumber }}</p>
            </div>

            @if($expectedDelivery)
                <div class="inline-flex items-center gap-2 bg-emerald-50 border border-emerald-200 rounded-2xl px-5 py-3 mb-6">
                    <span class="text-lg">🚚</span>
                    <span class="text-sm font-bold text-emerald-700">Expected Delivery: {{ $expectedDelivery }}</span>
                </div>
            @endif

            <p class="text-stone-400 text-sm max-w-sm mx-auto mb-6 leading-relaxed">
                We'll contact you on WhatsApp shortly to confirm your delivery details. Thank you for choosing Merza! 🌿
            </p>

            {{-- Payment screenshot upload --}}
            <div class="bg-white border-2 border-amber-100 rounded-3xl p-5 mb-6 text-left max-w-sm mx-auto">
                @if($screenshotUploaded)
                    <p class="flex items-center gap-2 text-sm font-bold text-emerald-700">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        Payment screenshot received. Thank you!
                    </p>
                @else
                    <p class="text-xs font-bold text-stone-500 uppercase tracking-wide mb-2">Already paid?</p>
                    <p class="text-xs text-stone-400 mb-3">Share your payment screenshot so we can confirm faster.</p>
                    <input type="file" wire:model="paymentScreenshot" accept="image/*"
                           class="block w-full text-xs text-stone-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                    @error('paymentScreenshot') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    <div wire:loading wire:target="paymentScreenshot" class="text-xs text-stone-400 mt-1">Uploading…</div>
                    @if($paymentScreenshot)
                        <button wire:click="uploadScreenshot" wire:loading.attr="disabled"
                                class="mt-3 w-full text-center bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold py-2.5 rounded-xl transition-colors">
                            Submit Screenshot
                        </button>
                    @endif
                @endif
            </div>

            <div class="flex flex-col sm:flex-row gap-3 justify-center flex-wrap">
                @auth
                    <a href="{{ route('account.order.detail', $orderId) }}"
                       class="inline-flex items-center justify-center gap-2 bg-white border-2 border-amber-200 text-amber-700 font-extrabold px-6 py-4 rounded-2xl hover:bg-amber-50 transition-all">
                        Track Order
                    </a>
                @endauth
                <a href="{{ URL::signedRoute('customer.orders.invoice', ['order' => $orderId]) }}"
                   class="inline-flex items-center justify-center gap-2 bg-white border-2 border-amber-200 text-amber-700 font-extrabold px-6 py-4 rounded-2xl hover:bg-amber-50 transition-all">
                    Download Invoice
                </a>
                <a href="https://wa.me/919360064278?text=Hi%2C+my+order+number+is+{{ $orderNumber }}.+Can+you+confirm+delivery+details?"
                   target="_blank"
                   class="inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold px-6 py-4 rounded-2xl transition-all shadow-lg">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    Track on WhatsApp
                </a>
            </div>
            <div class="mt-4">
                <a href="{{ route('home') }}" class="text-sm font-semibold text-stone-400 hover:text-amber-600 transition-colors">
                    ← Back to Home
                </a>
            </div>
        </div>

    @else
        {{-- ══════════════════════════════════════ --}}
        {{-- CHECKOUT FORM --}}
        {{-- ══════════════════════════════════════ --}}

        {{-- Progress steps --}}
        <div class="mb-8">
            <div class="flex items-center justify-center gap-2 md:gap-4">
                @foreach([['1', 'Delivery Details'], ['2', 'Payment']] as $i => [$num, $label])
                    <div class="flex items-center gap-2 {{ $i < 1 ? 'flex-1' : '' }}">
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-extrabold
                                         {{ $i === 0 ? 'bg-gradient-to-br from-amber-500 to-orange-500 text-white shadow-md' : 'bg-amber-100 text-amber-600' }}">
                                {{ $num }}
                            </span>
                            <span class="hidden sm:block text-sm font-bold {{ $i === 0 ? 'text-amber-700' : 'text-stone-400' }}">{{ $label }}</span>
                        </div>
                        @if($i < 1)
                            <div class="flex-1 h-0.5 bg-gradient-to-r from-amber-200 to-stone-100 mx-2"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 text-white flex items-center justify-center shadow">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <h1 class="text-2xl font-extrabold text-brand-green-dark">Checkout</h1>
        </div>

        @if($items->isEmpty())
            <div class="text-center py-16 bg-white rounded-3xl border border-amber-100">
                <p class="text-stone-400 text-lg mb-4">Your cart is empty.</p>
                <a href="{{ route('products.index') }}"
                   class="inline-flex items-center gap-2 bg-amber-500 text-white font-bold px-6 py-3 rounded-2xl hover:bg-amber-600 transition-colors">
                    Shop Now
                </a>
            </div>
        @else
            @error('cart') <p class="bg-red-50 border border-red-200 text-red-600 text-sm px-4 py-3 rounded-2xl mb-4">{{ $message }}</p> @enderror

            <div class="grid lg:grid-cols-3 gap-6">

                {{-- ── Order summary sidebar (first on mobile, right col on desktop via explicit grid placement) ── --}}
                <div class="lg:col-start-3 lg:row-start-1">
                    <div class="bg-white rounded-3xl border border-amber-100 shadow-sm overflow-hidden lg:sticky lg:top-24">

                        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-b border-amber-100 px-5 py-4">
                            <h2 class="font-extrabold text-stone-800">Your Order</h2>
                        </div>

                        <div class="p-5 space-y-3">
                            @foreach($items as $item)
                                <div class="flex gap-3 items-center">
                                    <div class="w-12 h-12 rounded-xl flex-shrink-0 overflow-hidden border border-amber-100"
                                         style="background: linear-gradient(145deg, #fef9c3, #fef3c7);">
                                        <div class="w-full h-full flex items-center justify-center text-xl">🥭</div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-extrabold text-stone-800 truncate">{{ $item->product_name }}</p>
                                        <p class="text-[10px] text-stone-400">{{ $item->variant_name }} × {{ $item->qty }}</p>
                                        @if($item->free_gift_label ?? null)
                                            <p class="text-[10px] font-bold text-emerald-700">🎁 {{ $item->free_gift_label }}</p>
                                        @endif
                                    </div>
                                    <p class="text-sm font-extrabold text-amber-600 flex-shrink-0">₹{{ number_format($item->line_total, 2) }}</p>
                                </div>
                            @endforeach

                            <div class="border-t border-amber-100 pt-3 space-y-1.5 text-sm">
                                <div class="flex justify-between text-stone-500">
                                    <span>Subtotal</span>
                                    <span>₹{{ number_format($subtotal, 2) }}</span>
                                </div>
                                <div class="flex justify-between text-stone-500">
                                    <span>Total weight</span>
                                    <span>{{ number_format($weightKg, 2) }} kg</span>
                                </div>
                                @if($giftWeightKg > 0)
                                    <p class="text-[11px] text-emerald-600">🎁 Includes {{ number_format($giftWeightKg, 2) }} kg of free gifts — couriers charge for actual package weight.</p>
                                @endif

                                @if($breakdown)
                                    {{-- Delivery breakdown --}}
                                    <div class="bg-amber-50 rounded-lg p-2.5 space-y-1 text-xs text-stone-500">
                                        <div class="flex justify-between">
                                            <span>Zone ({{ $breakdown['zone'] }})</span>
                                            <span>₹{{ number_format($breakdown['rate_per_kg'], 0) }}/kg</span>
                                        </div>
                                        @if($breakdown['packing_weight_kg'] > 0)
                                            <div class="flex justify-between">
                                                <span>Packing material (+{{ $breakdown['packing_weight_kg'] }} kg)</span>
                                                <span>included</span>
                                            </div>
                                        @endif
                                        <div class="flex flex-wrap justify-between gap-y-0.5 font-semibold text-stone-600 border-t border-amber-100 pt-1">
                                            <span>Courier ({{ number_format($breakdown['chargeable_weight'], 2) }} kg × ₹{{ number_format($breakdown['rate_per_kg'], 0) }})</span>
                                            <span>₹{{ number_format($breakdown['shipping_cost'], 2) }}</span>
                                        </div>
                                        @if($breakdown['packing_charge'] > 0)
                                            <div class="flex justify-between">
                                                <span>Packing charge</span>
                                                <span>₹{{ number_format($breakdown['packing_charge'], 2) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex justify-between font-semibold text-stone-600">
                                        <span>Delivery</span>
                                        <span>₹{{ number_format($breakdown['total_fee'], 2) }}</span>
                                    </div>
                                @else
                                    <div class="flex justify-between text-stone-400 text-xs">
                                        <span>Delivery</span>
                                        <span>&nbsp;</span>
                                    </div>
                                @endif

                                <div class="flex justify-between font-extrabold text-stone-900 pt-1 border-t border-amber-100">
                                    <span>Total</span>
                                    <span class="text-amber-600 text-lg">₹{{ number_format($total, 2) }}</span>
                                </div>
                            </div>

                            <a href="{{ route('cart.index') }}"
                               class="flex items-center justify-center gap-1 text-xs text-stone-400 hover:text-amber-600 transition-colors mt-2 font-medium">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Edit Cart
                            </a>
                        </div>
                    </div>
                </div>

                {{-- ── Form ── --}}
                <form wire:submit="placeOrder" class="lg:col-start-1 lg:col-span-2 lg:row-start-1 space-y-4">

                    {{-- Delivery Details --}}
                    <div class="bg-white rounded-3xl border border-amber-100 shadow-sm overflow-hidden">
                        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-b border-amber-100 px-5 py-4 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-amber-500 text-white flex items-center justify-center text-xs font-bold">1</span>
                            <h2 class="font-extrabold text-stone-800">Delivery Details</h2>
                        </div>
                        <div class="p-5 grid sm:grid-cols-2 gap-4">

                            <div>
                                <label class="block text-xs font-extrabold text-stone-600 mb-1.5 uppercase tracking-wide">Full Name *</label>
                                <input wire:model="customer_name" type="text" placeholder="Ahmad bin Ali"
                                       class="w-full border-2 {{ $errors->has('customer_name') ? 'border-red-300 bg-red-50' : 'border-stone-200 focus:border-amber-400' }} rounded-xl px-4 py-3 text-base focus:outline-none transition-colors bg-white placeholder-stone-300">
                                @error('customer_name') <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-extrabold text-stone-600 mb-1.5 uppercase tracking-wide">Phone Number *</label>
                                <input wire:model="customer_phone" type="tel" placeholder="93600 64278"
                                       class="w-full border-2 {{ $errors->has('customer_phone') ? 'border-red-300 bg-red-50' : 'border-stone-200 focus:border-amber-400' }} rounded-xl px-4 py-3 text-base focus:outline-none transition-colors bg-white placeholder-stone-300">
                                @error('customer_phone') <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                                <p class="text-[11px] text-stone-400 mt-1.5 leading-relaxed">
                                    📱 We'll send order updates to this number via WhatsApp. Reply <strong>STOP</strong> to that message anytime to opt out.
                                </p>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-extrabold text-stone-600 mb-1.5 uppercase tracking-wide">Address *</label>
                                <textarea wire:model="delivery_address" rows="2" placeholder="No. 12, Jalan Makmur, Taman Bahagia…"
                                          class="w-full border-2 {{ $errors->has('delivery_address') ? 'border-red-300 bg-red-50' : 'border-stone-200 focus:border-amber-400' }} rounded-xl px-4 py-3 text-base focus:outline-none transition-colors bg-white placeholder-stone-300 resize-none"></textarea>
                                @error('delivery_address') <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-extrabold text-stone-600 mb-1.5 uppercase tracking-wide">Pincode *</label>
                                <input wire:model.live.debounce.500ms="postcode" type="text" inputmode="numeric" maxlength="6" placeholder="625513"
                                       class="w-full border-2 {{ $errors->has('postcode') || $errors->has('city') ? 'border-red-300 bg-red-50' : 'border-stone-200 focus:border-amber-400' }} rounded-xl px-4 py-3 text-base focus:outline-none transition-colors bg-white placeholder-stone-300">
                                <div wire:loading wire:target="postcode" class="text-xs text-amber-500 mt-1">Looking up pincode…</div>
                                @if($pincodeAutoFilled)
                                    <p class="text-[11px] text-emerald-600 mt-1.5 font-medium">✓ District &amp; state detected automatically</p>
                                @elseif($pincodeLookupFailed)
                                    <p class="text-[11px] text-amber-600 mt-1.5">Couldn't auto-detect — please fill district &amp; state below.</p>
                                @endif
                                @error('postcode') <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-extrabold text-stone-600 mb-1.5 uppercase tracking-wide">Landmark <span class="font-normal text-stone-400 normal-case">(optional)</span></label>
                                <input wire:model="landmark" type="text" placeholder="Near bus stand"
                                       class="w-full border-2 border-stone-200 focus:border-amber-400 rounded-xl px-4 py-3 text-base focus:outline-none transition-colors bg-white placeholder-stone-300">
                            </div>

                            <div>
                                <label class="block text-xs font-extrabold text-stone-600 mb-1.5 uppercase tracking-wide">District *</label>
                                <input wire:model="city" type="text" placeholder="Bodinayakanur"
                                       class="w-full border-2 {{ $errors->has('city') ? 'border-red-300 bg-red-50' : 'border-stone-200 focus:border-amber-400' }} rounded-xl px-4 py-3 text-base focus:outline-none transition-colors bg-white placeholder-stone-300">
                                @error('city') <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-extrabold text-stone-600 mb-1.5 uppercase tracking-wide">State *</label>
                                <select wire:model.live="state"
                                        class="w-full border-2 {{ $errors->has('state') ? 'border-red-300 bg-red-50' : 'border-stone-200 focus:border-amber-400' }} rounded-xl px-4 py-3 text-base focus:outline-none transition-colors bg-white text-stone-700">
                                    <option value="">Select a state…</option>
                                    @foreach($stateOptions as $s)
                                        <option value="{{ $s }}">{{ $s }}</option>
                                    @endforeach
                                </select>
                                <p class="text-[11px] text-stone-400 mt-1.5">Only states we currently deliver to are listed.</p>
                                @error('state') <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Payment --}}
                    <div class="bg-white rounded-3xl border border-amber-100 shadow-sm overflow-hidden">
                        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-b border-amber-100 px-5 py-4 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-amber-500 text-white flex items-center justify-center text-xs font-bold">2</span>
                            <h2 class="font-extrabold text-stone-800">Payment Method</h2>
                        </div>
                        <div class="p-5 space-y-5">

                            {{-- Method tiles --}}
                            <div class="grid sm:grid-cols-2 gap-3">
                                <div class="text-left p-4 rounded-2xl border-2 border-amber-500 bg-amber-50 shadow-sm">
                                    <p class="font-extrabold text-sm text-stone-800">GPay Number</p>
                                    <p class="text-xs text-stone-400 mt-0.5">Pay via GPay, PhonePe, or any UPI app</p>
                                </div>

                                <div class="text-left p-4 rounded-2xl border-2 border-stone-100 bg-stone-50 opacity-60 cursor-not-allowed">
                                    <p class="font-extrabold text-sm text-stone-500">Card Payment</p>
                                    <p class="text-xs text-stone-400 mt-0.5">Coming soon</p>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row items-center gap-5 bg-amber-50 rounded-2xl p-5">
                                <div class="text-center sm:text-left flex-1">
                                    <p class="text-xs font-extrabold text-stone-500 uppercase tracking-wide mb-1">Pay via GPay / PhonePe / any UPI app</p>
                                    <p class="text-2xl font-extrabold text-amber-600 mb-3">₹{{ number_format($total, 2) }}</p>
                                    <p class="text-xs text-stone-500 mb-1.5">Send payment to our GPay number:</p>
                                    <div x-data="{ copied: false }" class="inline-flex items-center gap-2">
                                        <button type="button"
                                                @click="navigator.clipboard.writeText('8667696278'); copied = true; setTimeout(() => copied = false, 2000)"
                                                class="group inline-flex items-center gap-2 font-mono font-bold text-lg text-stone-800 bg-white rounded-xl px-4 py-2.5 border-2 border-amber-200 hover:border-amber-400 transition-colors">
                                            <span>86676 96278</span>
                                            <svg x-show="!copied" class="w-4 h-4 text-stone-400 group-hover:text-amber-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                            <svg x-show="copied" x-cloak class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                        <span x-show="copied" x-cloak x-transition class="text-xs font-bold text-emerald-600">Copied!</span>
                                    </div>
                                    <p class="text-xs text-stone-500 mt-2">Rajalakshmi Senthilkumar</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                            wire:loading.attr="disabled"
                            class="w-full bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 disabled:opacity-60 text-white font-extrabold py-5 rounded-2xl transition-all text-base shadow-xl shadow-amber-200/60 hover:shadow-2xl hover:-translate-y-0.5">
                        <span wire:loading.remove wire:target="placeOrder" class="flex items-center justify-center gap-2">
                            🔒 Pay Securely
                        </span>
                        <span wire:loading wire:target="placeOrder" class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Placing your order…
                        </span>
                    </button>

                    <p class="text-center text-xs text-stone-400">
                        🔒 Secure checkout · By ordering you agree to our <a href="#" class="underline">terms</a>
                    </p>
                </form>

            </div>
        @endif
    @endif
</div>
