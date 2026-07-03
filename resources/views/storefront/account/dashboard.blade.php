<x-layouts.storefront title="My Account">
    <div class="max-w-4xl mx-auto px-4 py-10">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-extrabold text-stone-900">Hello, {{ auth()->user()->name }} 👋</h1>
                <p class="text-stone-500 text-sm mt-0.5">Manage your orders and account details</p>
            </div>
            <form method="POST" action="{{ route('customer.logout') }}">
                @csrf
                <button type="submit" class="text-sm text-stone-500 hover:text-red-600 transition-colors">Sign out</button>
            </form>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 gap-4 mb-8">
            <div class="bg-amber-50 border border-amber-100 rounded-2xl p-5">
                <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-1">Total Orders</p>
                <p class="text-3xl font-extrabold text-amber-600">{{ $totalOrders }}</p>
            </div>
            <div class="bg-green-50 border border-green-100 rounded-2xl p-5">
                <p class="text-xs font-semibold text-green-700 uppercase tracking-wide mb-1">Total Spent</p>
                <p class="text-3xl font-extrabold text-green-600">₹{{ number_format($totalSpent, 2) }}</p>
            </div>
        </div>

        {{-- Quick links --}}
        <div class="grid grid-cols-2 gap-3 mb-8">
            <a href="{{ route('account.orders') }}" class="flex items-center gap-3 bg-white border border-stone-100 rounded-xl p-4 hover:border-amber-300 transition-all group">
                <div class="w-9 h-9 bg-amber-100 rounded-lg flex items-center justify-center group-hover:bg-amber-200 transition-colors">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <span class="text-sm font-semibold text-stone-700">My Orders</span>
            </a>
            <a href="{{ route('account.profile') }}" class="flex items-center gap-3 bg-white border border-stone-100 rounded-xl p-4 hover:border-amber-300 transition-all group">
                <div class="w-9 h-9 bg-stone-100 rounded-lg flex items-center justify-center group-hover:bg-stone-200 transition-colors">
                    <svg class="w-5 h-5 text-stone-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <span class="text-sm font-semibold text-stone-700">Profile</span>
            </a>
        </div>

        {{-- Recent Orders --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-stone-800">Recent Orders</h2>
                @if($totalOrders > 0)
                    <a href="{{ route('account.orders') }}" class="text-sm text-amber-600 font-semibold hover:text-amber-700">View all →</a>
                @endif
            </div>

            @if($recentOrders->isEmpty())
                <div class="bg-white border border-dashed border-stone-200 rounded-2xl p-10 text-center">
                    <div class="text-4xl mb-3">🛒</div>
                    <p class="text-stone-500 text-sm mb-4">You haven't placed any orders yet.</p>
                    <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition-all">
                        Shop Mukkani Fruits
                    </a>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($recentOrders as $order)
                        <a href="{{ route('account.order.detail', $order) }}" class="flex items-center justify-between bg-white border border-stone-100 rounded-xl px-5 py-4 hover:border-amber-300 transition-all group">
                            <div>
                                <p class="text-sm font-bold text-stone-800 group-hover:text-amber-700 transition-colors">
                                    #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
                                </p>
                                <p class="text-xs text-stone-400 mt-0.5">{{ $order->created_at->format('d M Y') }} · {{ $order->items->count() }} item(s)</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-stone-800">₹{{ number_format($order->total, 2) }}</p>
                                @php
                                    $statusColors = [
                                        'pending'    => 'bg-yellow-100 text-yellow-700',
                                        'confirmed'  => 'bg-blue-100 text-blue-700',
                                        'preparing'  => 'bg-purple-100 text-purple-700',
                                        'delivering' => 'bg-orange-100 text-orange-700',
                                        'delivered'  => 'bg-green-100 text-green-700',
                                        'cancelled'  => 'bg-red-100 text-red-700',
                                    ];
                                    $colorClass = $statusColors[$order->status] ?? 'bg-stone-100 text-stone-600';
                                @endphp
                                <span class="inline-block text-xs font-semibold px-2 py-0.5 rounded-full mt-1 {{ $colorClass }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layouts.storefront>
