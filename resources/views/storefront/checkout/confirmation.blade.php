<x-layouts.storefront title="Order {{ $order->order_number }} — Payment {{ $status === 'SUCCESS' ? 'Confirmed' : 'Status' }}">
    <div class="max-w-lg mx-auto px-4 py-12 text-center">

        @if($status === 'SUCCESS')
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
            <h1 class="text-3xl font-extrabold text-brand-green-dark mb-2">Payment Successful! 🥭</h1>
            <p class="text-stone-500 mb-4">Your fresh fruits are being prepared.</p>
        @elseif(in_array($status, ['FAILED', 'EXPIRED', 'TIMEOUT', 'CANCELLED']))
            <div class="w-28 h-28 mx-auto mb-6 rounded-3xl bg-gradient-to-br from-red-400 to-rose-600 flex items-center justify-center text-6xl shadow-2xl shadow-red-200">
                😕
            </div>
            <h1 class="text-3xl font-extrabold text-stone-800 mb-2">Payment {{ ucfirst(strtolower($status)) }}</h1>
            <p class="text-stone-500 mb-4">Your order was placed but payment didn't go through. No amount was captured for this attempt.</p>
        @else
            <div class="w-28 h-28 mx-auto mb-6 rounded-3xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-6xl shadow-2xl shadow-amber-200">
                ⏳
            </div>
            <h1 class="text-3xl font-extrabold text-stone-800 mb-2">Confirming Payment…</h1>
            <p class="text-stone-500 mb-4">We're still confirming your payment with the bank. This page will show the final status shortly — or check your order status below.</p>
        @endif

        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-200 rounded-3xl p-5 mb-6">
            <p class="text-xs text-amber-600 font-bold uppercase tracking-widest mb-1">Order Number</p>
            <p class="text-3xl font-extrabold text-amber-600">{{ $order->order_number }}</p>
        </div>

        <p class="text-stone-400 text-sm max-w-sm mx-auto mb-6 leading-relaxed">
            We'll contact you on WhatsApp shortly to confirm your delivery details. Thank you for choosing Merza! 🌿
        </p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center flex-wrap">
            <a href="{{ route('track.index', ['order' => $order->order_number]) }}"
               class="inline-flex items-center justify-center gap-2 bg-white border-2 border-amber-200 text-amber-700 font-extrabold px-6 py-4 rounded-2xl hover:bg-amber-50 transition-all">
                Track Order
            </a>
            <a href="{{ URL::signedRoute('customer.orders.invoice', ['order' => $order->id]) }}"
               class="inline-flex items-center justify-center gap-2 bg-white border-2 border-amber-200 text-amber-700 font-extrabold px-6 py-4 rounded-2xl hover:bg-amber-50 transition-all">
                Download Invoice
            </a>
            <a href="https://wa.me/919360064278?text=Hi%2C+my+order+number+is+{{ $order->order_number }}.+Can+you+confirm+delivery+details%3F"
               target="_blank"
               class="inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold px-6 py-4 rounded-2xl transition-all shadow-lg">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                {{ $status === 'SUCCESS' ? 'Track on WhatsApp' : 'Get Help on WhatsApp' }}
            </a>
        </div>

        <div class="mt-4">
            <a href="{{ route('home') }}" class="text-sm font-semibold text-stone-400 hover:text-amber-600 transition-colors">
                ← Back to Home
            </a>
        </div>
    </div>
</x-layouts.storefront>
