<x-layouts.storefront title="Fresh Premium Tropical Fruits">

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- HERO --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <section class="relative overflow-hidden" style="background: linear-gradient(135deg, #064e3b 0%, #065f46 30%, #047857 55%, #d97706 85%, #f97316 100%);">

        {{-- Decorative blobs --}}
        <div class="absolute top-0 right-0 w-96 h-96 rounded-full opacity-10" style="background: radial-gradient(circle, #facc15 0%, transparent 70%); transform: translate(30%, -30%);"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 rounded-full opacity-10" style="background: radial-gradient(circle, #fb923c 0%, transparent 70%); transform: translate(-30%, 30%);"></div>

        <div class="relative max-w-7xl mx-auto px-4 py-16 md:py-24 flex flex-col md:flex-row items-center gap-10">

            {{-- Left: Text --}}
            <div class="flex-1 text-center md:text-left z-10">
                <span class="inline-flex items-center gap-2 bg-white/15 backdrop-blur-sm border border-white/25 text-amber-300 text-xs font-bold px-4 py-1.5 rounded-full mb-5 uppercase tracking-widest">
                    <span class="w-2 h-2 rounded-full bg-green-400 pulse-dot"></span>
                    Farm Fresh · Daily Harvest
                </span>

                <h1 class="text-4xl md:text-6xl font-extrabold leading-[1.1] text-white mb-4">
                    Tropical Fruits<br>
                    <span class="text-gradient">You'll Crave</span><br>
                    <span class="text-white">Every Day.</span>
                </h1>

                <p class="text-emerald-100 text-base md:text-lg mb-8 max-w-md mx-auto md:mx-0 leading-relaxed">
                    Premium Mangoes, Jackfruit, Banana Red & more — harvested ripe and delivered straight to your door across India.
                </p>

                <div class="flex flex-col sm:flex-row gap-3 justify-center md:justify-start">
                    <a href="{{ route('products.index') }}"
                       class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-white font-extrabold px-8 py-4 rounded-2xl transition-all text-base shadow-lg shadow-amber-900/30 hover:shadow-xl hover:-translate-y-0.5">
                        Shop All Fruits
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                    <a href="https://wa.me/918667696278?text=Hi%2C+I+want+to+order+from+Merza!" target="_blank"
                       class="inline-flex items-center justify-center gap-2 bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/30 text-white font-bold px-8 py-4 rounded-2xl transition-all text-base">
                        <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        Order via WhatsApp
                    </a>
                </div>

                {{-- Social proof bar --}}
                <div class="mt-8 flex flex-wrap items-center gap-5 justify-center md:justify-start">
                    <div class="flex items-center gap-2">
                        <div class="flex -space-x-2">
                            @foreach(['🧑', '👩', '👨', '🧕'] as $face)
                                <span class="w-8 h-8 rounded-full bg-amber-200 border-2 border-white flex items-center justify-center text-sm">{{ $face }}</span>
                            @endforeach
                        </div>
                        <span class="text-xs text-emerald-200">500+ happy customers</span>
                    </div>
                    <div class="flex items-center gap-1">
                        @for($i = 0; $i < 5; $i++)
                            <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                        <span class="text-xs text-emerald-200 ml-1">4.9/5 rating</span>
                    </div>
                </div>
            </div>

            {{-- Right: Fruit illustration --}}
            <div class="flex-shrink-0 relative w-64 h-64 md:w-80 md:h-80 z-10">
                {{-- Main circle --}}
                <div class="absolute inset-0 rounded-full bg-white/10 backdrop-blur-sm border border-white/20 flex items-center justify-center text-9xl float-fruit shadow-2xl">
                    🥭
                </div>
                {{-- Orbiting fruits --}}
                <div class="absolute -top-4 -right-4 w-16 h-16 rounded-full bg-yellow-400/20 backdrop-blur-sm border border-yellow-400/30 flex items-center justify-center text-3xl float-fruit-2 shadow-lg">🍌</div>
                <div class="absolute -bottom-4 -left-4 w-16 h-16 rounded-full bg-emerald-400/20 backdrop-blur-sm border border-emerald-400/30 flex items-center justify-center text-3xl float-fruit-3 shadow-lg">🍈</div>
                <div class="absolute top-1/2 -right-8 w-12 h-12 rounded-full bg-orange-400/20 backdrop-blur-sm border border-orange-400/30 flex items-center justify-center text-2xl float-fruit shadow-md" style="animation-delay: 2s">🍋</div>
            </div>
        </div>

        {{-- Wave bottom --}}
        <div class="wave-bottom">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 60" preserveAspectRatio="none" style="height:60px;">
                <path fill="#fffbeb" d="M0,30 C360,60 1080,0 1440,30 L1440,60 L0,60 Z"/>
            </svg>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- CATEGORIES --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <section class="max-w-7xl mx-auto px-4 py-12">
        <div class="text-center mb-8">
            <span class="text-xs font-bold text-amber-600 uppercase tracking-widest">What We Grow</span>
            <h2 class="text-3xl md:text-4xl font-extrabold text-stone-900 mt-1">Our Tropical Selection</h2>
            <p class="text-stone-500 mt-2">Hand-picked from the finest farms across Southeast Asia</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            @foreach([
                [
                    'emoji' => '🥭',
                    'name'  => 'Premium Mangoes',
                    'sub'   => 'Alphonso & Harum Manis',
                    'from'  => '#fef9c3', 'to' => '#fef08a',
                    'ring'  => 'ring-yellow-300',
                    'badge' => 'bg-yellow-100 text-yellow-700',
                    'label' => 'Bestseller',
                ],
                [
                    'emoji' => '🍌',
                    'name'  => 'Banana Red',
                    'sub'   => 'Sweet & Nutritious',
                    'from'  => '#fce7f3', 'to' => '#fbcfe8',
                    'ring'  => 'ring-pink-300',
                    'badge' => 'bg-pink-100 text-pink-700',
                    'label' => 'Popular',
                ],
                [
                    'emoji' => '🍈',
                    'name'  => 'Vietnam Gold Jackfruit',
                    'sub'   => 'Premium Variety',
                    'from'  => '#d1fae5', 'to' => '#a7f3d0',
                    'ring'  => 'ring-emerald-300',
                    'badge' => 'bg-emerald-100 text-emerald-700',
                    'label' => 'Fresh Pick',
                ],
                [
                    'emoji' => '🍋',
                    'name'  => 'Freeze Dried',
                    'sub'   => 'Long Shelf Life',
                    'from'  => '#fef3c7', 'to' => '#fde68a',
                    'ring'  => 'ring-amber-300',
                    'badge' => 'bg-amber-100 text-amber-700',
                    'label' => 'New',
                ],
                [
                    'emoji' => '🧃',
                    'name'  => 'Pulp & Puree',
                    'sub'   => 'Mango & Jackfruit',
                    'from'  => '#ffedd5', 'to' => '#fed7aa',
                    'ring'  => 'ring-orange-300',
                    'badge' => 'bg-orange-100 text-orange-700',
                    'label' => 'B2B',
                ],
            ] as $cat)
                <a href="{{ route('products.index') }}"
                   class="fruit-card group rounded-3xl overflow-hidden ring-2 {{ $cat['ring'] }} hover:ring-4 hover:scale-105 hover:shadow-xl transition-all duration-300 cursor-pointer"
                   style="background: linear-gradient(145deg, {{ $cat['from'] }}, {{ $cat['to'] }})">
                    <div class="p-5 text-center">
                        <div class="text-5xl mb-3 group-hover:scale-110 transition-transform duration-300">{{ $cat['emoji'] }}</div>
                        <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-full mb-2 {{ $cat['badge'] }}">{{ $cat['label'] }}</span>
                        <h3 class="font-extrabold text-sm text-stone-800 leading-tight">{{ $cat['name'] }}</h3>
                        <p class="text-xs text-stone-500 mt-1">{{ $cat['sub'] }}</p>
                        <div class="mt-3 text-xs font-semibold text-stone-600 flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            View all
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- TRUST SIGNALS --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <section class="relative overflow-hidden py-12" style="background: linear-gradient(135deg, #fef3c7 0%, #fff7ed 50%, #ecfdf5 100%);">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                @foreach([
                    [
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
                        'color' => 'from-amber-400 to-orange-500',
                        'title' => 'Same-Day Delivery',
                        'sub'   => 'Order before 12pm',
                    ],
                    [
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>',
                        'color' => 'from-emerald-400 to-emerald-600',
                        'title' => '100% Farm Fresh',
                        'sub'   => 'Direct from source',
                    ],
                    [
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>',
                        'color' => 'from-green-400 to-green-600',
                        'title' => 'WhatsApp Support',
                        'sub'   => 'Mon–Sat, 9am–6pm',
                    ],
                    [
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
                        'color' => 'from-orange-400 to-red-500',
                        'title' => 'Bulk B2B Orders',
                        'sub'   => 'Wholesale pricing',
                    ],
                ] as $trust)
                    <div class="flex flex-col items-center text-center group">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br {{ $trust['color'] }} text-white flex items-center justify-center mb-4 shadow-lg group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $trust['icon'] !!}</svg>
                        </div>
                        <h3 class="font-extrabold text-stone-800 text-sm mb-1">{{ $trust['title'] }}</h3>
                        <p class="text-xs text-stone-500">{{ $trust['sub'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- FEATURED PRODUCTS --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($featured->isNotEmpty())
    <section class="max-w-7xl mx-auto px-4 py-12">
        <div class="flex items-end justify-between mb-8">
            <div>
                <span class="text-xs font-bold text-amber-600 uppercase tracking-widest">Editor's Pick</span>
                <h2 class="text-3xl md:text-4xl font-extrabold text-stone-900 mt-1">Featured Fruits</h2>
            </div>
            <a href="{{ route('products.index') }}"
               class="hidden md:inline-flex items-center gap-1 text-sm font-bold text-amber-600 hover:text-amber-700 transition-colors">
                View all
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($featured as $product)
                <a href="{{ route('products.show', $product->slug) }}"
                   class="group bg-white rounded-3xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-amber-100">

                    <div class="relative aspect-square overflow-hidden" style="background: linear-gradient(135deg, #fef9c3, #fef3c7);">
                        @if($product->getFirstMediaUrl('thumbnail', 'thumb'))
                            <img src="{{ $product->getFirstMediaUrl('thumbnail', 'thumb') }}"
                                 alt="{{ $product->name }}"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-7xl group-hover:scale-110 transition-transform duration-300">🥭</div>
                        @endif
                        <div class="absolute top-3 left-3">
                            <span class="bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[10px] font-bold px-2.5 py-1 rounded-full shadow">⭐ Featured</span>
                        </div>
                    </div>

                    <div class="p-4">
                        <p class="text-[10px] text-amber-600 font-bold uppercase tracking-wider mb-1">{{ $product->category?->name }}</p>
                        <h3 class="font-extrabold text-sm text-stone-800 leading-tight line-clamp-2 mb-2">{{ $product->name }}</h3>
                        <div class="flex items-center justify-between">
                            <span class="text-amber-600 font-extrabold text-base">
                                @if($product->activeVariants->isNotEmpty())
                                    From RM{{ number_format($product->activeVariants->min('price'), 2) }}
                                @else
                                    RM{{ number_format($product->base_price, 2) }}
                                @endif
                            </span>
                            <span class="w-8 h-8 rounded-xl bg-amber-500 flex items-center justify-center text-white shadow group-hover:bg-orange-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6 text-center md:hidden">
            <a href="{{ route('products.index') }}"
               class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-bold px-6 py-3 rounded-2xl transition-colors">
                View All Products
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </section>
    @endif

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- HOW IT WORKS --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <section class="bg-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-10">
                <span class="text-xs font-bold text-amber-600 uppercase tracking-widest">Simple & Easy</span>
                <h2 class="text-3xl font-extrabold text-stone-900 mt-1">Order in 3 Steps</h2>
            </div>

            <div class="grid md:grid-cols-3 gap-8 relative">
                {{-- connector line --}}
                <div class="hidden md:block absolute top-8 left-1/4 right-1/4 h-0.5 bg-gradient-to-r from-amber-200 via-orange-300 to-emerald-300"></div>

                @foreach([
                    ['num' => '01', 'emoji' => '🛍️', 'title' => 'Browse & Pick', 'desc' => 'Choose from our fresh selection of premium tropical fruits by category or search.', 'color' => 'from-amber-400 to-yellow-500'],
                    ['num' => '02', 'emoji' => '📦', 'title' => 'Add to Cart', 'desc' => 'Select your variants, quantities, and checkout securely in minutes.', 'color' => 'from-orange-400 to-red-400'],
                    ['num' => '03', 'emoji' => '🚚', 'title' => 'Fast Delivery', 'desc' => 'We pack with care and deliver fresh to your doorstep, same day available.', 'color' => 'from-emerald-400 to-green-500'],
                ] as $step)
                    <div class="flex flex-col items-center text-center relative z-10">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br {{ $step['color'] }} text-white font-extrabold text-xl flex items-center justify-center shadow-lg mb-4">
                            {{ $step['emoji'] }}
                        </div>
                        <span class="text-xs font-bold text-stone-400 uppercase tracking-widest mb-1">Step {{ $step['num'] }}</span>
                        <h3 class="font-extrabold text-lg text-stone-800 mb-2">{{ $step['title'] }}</h3>
                        <p class="text-sm text-stone-500 max-w-xs">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- WHATSAPP CTA BANNER --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <section class="max-w-7xl mx-auto px-4 py-12">
        <div class="relative overflow-hidden rounded-3xl p-8 md:p-12 text-white" style="background: linear-gradient(135deg, #047857 0%, #059669 40%, #065f46 100%);">

            {{-- Decorative circles --}}
            <div class="absolute top-0 right-0 w-64 h-64 rounded-full opacity-10" style="background: radial-gradient(circle, white 0%, transparent 70%); transform: translate(30%, -30%);"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 rounded-full opacity-10" style="background: radial-gradient(circle, #facc15 0%, transparent 70%); transform: translate(-20%, 20%);"></div>

            <div class="relative flex flex-col md:flex-row items-center gap-8">
                <div class="flex-1 text-center md:text-left">
                    <div class="text-4xl mb-3">💬</div>
                    <h2 class="text-2xl md:text-3xl font-extrabold mb-3">Prefer Ordering on WhatsApp?</h2>
                    <p class="text-emerald-100 text-base max-w-lg">
                        Get personalised recommendations, custom packaging, bulk pricing, and fast replies. We're just a message away!
                    </p>
                </div>
                <div class="flex-shrink-0 flex flex-col items-center gap-3">
                    <a href="https://wa.me/918667696278?text=Hi+Merza%2C+I+want+to+place+an+order!" target="_blank"
                       class="inline-flex items-center gap-3 bg-white text-emerald-800 font-extrabold px-8 py-4 rounded-2xl hover:bg-amber-50 transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5 text-base">
                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        Chat on WhatsApp
                    </a>
                    <span class="text-xs text-emerald-300">Usually replies in under 5 minutes</span>
                </div>
            </div>
        </div>
    </section>

</x-layouts.storefront>
