<x-layouts.storefront title="My Orders">

    <section class="max-w-3xl mx-auto px-4 py-10">

        <div class="mb-6">
            <h1 class="text-2xl font-extrabold text-stone-900">My Orders</h1>
            <p class="text-stone-500 text-sm mt-1">Logged in as {{ auth()->user()->email }}</p>
        </div>

        @if($orders->isEmpty())
            <div class="bg-white rounded-2xl border border-amber-100 shadow-sm p-12 text-center">
                <div class="text-5xl mb-4">🥭</div>
                <h2 class="text-lg font-bold text-stone-800 mb-2">No orders yet</h2>
                <p class="text-stone-500 text-sm mb-6">You haven't placed any orders with us. Browse our fresh fruits and place your first order!</p>
                <a href="{{ route('products.index') }}"
                   class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-bold px-6 py-3 rounded-xl text-sm transition-all">
                    Shop Now
                </a>
            </div>
        @else
            <div class="space-y-4">
                @foreach($orders as $order)
                    <div class="bg-white rounded-2xl border border-amber-100 shadow-sm overflow-hidden">
                        {{-- Order header --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 border-b border-stone-50">
                            <div>
                                <span class="font-bold text-stone-900 text-sm">{{ $order->order_number }}</span>
                                <span class="ml-3 text-xs text-stone-400">{{ $order->created_at->format('d M Y') }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                @php
                                    $badge = match($order->status) {
                                        'pending'    => 'bg-yellow-100 text-yellow-700',
                                        'confirmed'  => 'bg-blue-100 text-blue-700',
                                        'preparing'  => 'bg-purple-100 text-purple-700',
                                        'delivering' => 'bg-indigo-100 text-indigo-700',
                                        'delivered'  => 'bg-emerald-100 text-emerald-700',
                                        'cancelled'  => 'bg-red-100 text-red-700',
                                        default      => 'bg-stone-100 text-stone-600',
                                    };
                                    $label = match($order->status) {
                                        'pending'    => '⏳ Pending',
                                        'confirmed'  => '✅ Confirmed',
                                        'preparing'  => '📦 Preparing',
                                        'delivering' => '🚚 Out for Delivery',
                                        'delivered'  => '✔️ Delivered',
                                        'cancelled'  => '✖ Cancelled',
                                        default      => ucfirst($order->status),
                                    };
                                @endphp
                                <span class="text-xs font-bold px-3 py-1 rounded-full {{ $badge }}">{{ $label }}</span>
                                <span class="text-sm font-extrabold text-stone-900">₹{{ number_format($order->total, 2) }}</span>
                            </div>
                        </div>

                        {{-- Order items --}}
                        <div class="px-5 py-3 space-y-2">
                            @foreach($order->items as $item)
                                <div class="flex items-center justify-between text-sm">
                                    <div>
                                        <span class="font-medium text-stone-800">{{ $item->product_name }}</span>
                                        @if($item->variant_name)
                                            <span class="text-stone-400 text-xs ml-1">· {{ $item->variant_name }}</span>
                                        @endif
                                    </div>
                                    <div class="text-stone-500 text-xs">
                                        {{ $item->quantity }} × ₹{{ number_format($item->unit_price, 2) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Footer --}}
                        <div class="px-5 py-3 bg-stone-50 border-t border-stone-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-xs text-stone-500">
                            <span>Payment: <strong class="text-stone-700">{{ ucfirst(str_replace('_', ' ', $order->payment_method ?? 'N/A')) }}</strong>
                                · Status: <strong class="{{ $order->payment_status === 'paid' ? 'text-emerald-600' : 'text-yellow-600' }}">{{ ucfirst($order->payment_status ?? 'pending') }}</strong>
                            </span>
                            @if($order->tracking_number)
                                <span>Tracking: <strong class="text-stone-700">{{ $order->tracking_number }}</strong></span>
                            @endif
                            <a href="https://wa.me/918667696278?text=Hi%2C+I+need+help+with+order+{{ $order->order_number }}"
                               target="_blank"
                               class="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-700 font-semibold">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                Help with this order
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </section>

</x-layouts.storefront>
