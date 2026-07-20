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
                    Farm Fresh · 100% Natural · Bodinayakanur
                </span>

                <h1 class="text-4xl md:text-6xl font-extrabold leading-[1.1] text-white mb-4">
                    Farm Fresh Fruits.<br>
                    <span class="text-white">Delivered Today.</span>
                </h1>

                <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-5 justify-center md:justify-start">
                    <a href="{{ route('products.index') }}"
                       class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-white font-extrabold px-8 py-4 rounded-2xl transition-all text-base shadow-lg shadow-amber-900/30 hover:shadow-xl hover:-translate-y-0.5">
                        Order Now
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                    <a href="https://wa.me/919360064278?text=Hi%2C+I+want+to+order+from+Merza!" target="_blank"
                       class="inline-flex items-center justify-center gap-1.5 text-emerald-100 hover:text-white font-semibold text-sm underline underline-offset-4 decoration-white/30 hover:decoration-white transition-colors">
                        <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        or order via WhatsApp
                    </a>
                </div>

                {{-- Social proof bar (real numbers only) --}}
                <div class="mt-8 flex flex-wrap items-center gap-5 justify-center md:justify-start">
                    <div class="flex items-center gap-2">
                        <div class="flex -space-x-2">
                            @foreach(['🧑', '👩', '👨', '🧕'] as $face)
                                <span class="w-8 h-8 rounded-full bg-amber-200 border-2 border-white flex items-center justify-center text-sm">{{ $face }}</span>
                            @endforeach
                        </div>
                        <span class="text-xs text-emerald-200">3,500+ happy customers</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🚚</span>
                        <span class="text-xs text-emerald-200">2,000+ orders delivered</span>
                    </div>
                </div>
            </div>

            {{-- Right: Real fruit photo (falls back to illustration until a product photo is uploaded) --}}
            @php $heroImage = $featured->first()?->getFirstMediaUrl('images', 'card') ?: $featured->first()?->getFirstMediaUrl('thumbnail', 'thumb'); @endphp
            <div class="flex-shrink-0 relative w-64 h-64 md:w-80 md:h-80 z-10">
                @if($heroImage)
                    <div class="absolute inset-0 rounded-full overflow-hidden border-4 border-white/20 shadow-2xl float-fruit">
                        <img src="{{ $heroImage }}" alt="{{ $featured->first()->name }}" class="w-full h-full object-cover">
                    </div>
                @else
                    {{-- Main circle --}}
                    <div class="absolute inset-0 rounded-full bg-white/10 backdrop-blur-sm border border-white/20 flex items-center justify-center text-9xl float-fruit shadow-2xl">
                        🥭
                    </div>
                @endif
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
    {{-- TRUST KEYWORDS --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <section class="py-8 bg-brand-green-light/40">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4">
                @foreach([
                    ['🌾', 'Direct From Farmers'],
                    ['🌱', 'Naturally Ripened'],
                    ['📦', 'Freshly Packed'],
                    ['🚚', 'Fast Delivery'],
                    ['🔒', 'Secure Payment'],
                    ['⭐', 'Trusted Quality'],
                ] as [$icon, $label])
                    <div class="flex flex-col items-center text-center gap-1.5">
                        <span class="text-2xl">{{ $icon }}</span>
                        <span class="text-[11px] font-bold text-stone-700 leading-tight">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- TODAY'S FRESH ARRIVAL --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($todaysArrivals->isNotEmpty())
    <section class="max-w-7xl mx-auto px-4 py-12">
        <div class="flex items-end justify-between mb-8">
            <div>
                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 uppercase tracking-widest">
                    <span class="w-2 h-2 rounded-full bg-green-500 pulse-dot"></span>
                    Available Today · {{ now()->format('d M') }}
                </span>
                <h2 class="text-3xl md:text-4xl font-extrabold text-brand-green-dark mt-1">Today's Fresh Arrival</h2>
                <p class="text-stone-500 mt-1 text-sm">Picked and packed this morning — order before it's gone</p>
            </div>
            <a href="{{ route('products.index') }}"
               class="hidden md:inline-flex items-center gap-1 text-sm font-bold text-amber-600 hover:text-amber-700 transition-colors">
                View all
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            @foreach($todaysArrivals as $product)
                @php
                    $soldOut = $product->activeVariants->where('stock_qty', '>', 0)->isEmpty();
                    $thumbUrl = $product->getFirstMediaUrl('thumbnail', 'thumb') ?: $product->getFirstMediaUrl('images', 'thumb');
                @endphp
                <a href="{{ route('products.show', $product->slug) }}"
                   class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-1 border-2 border-emerald-100">

                    <div class="relative aspect-square overflow-hidden" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0);">
                        @if($thumbUrl)
                            <img src="{{ $thumbUrl }}"
                                 alt="{{ $product->name }}"
                                 loading="lazy"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <div class="w-full h-full items-center justify-center text-6xl group-hover:scale-110 transition-transform duration-300" style="display:none">🥭</div>
                        @else
                            <div class="w-full h-full flex items-center justify-center text-6xl group-hover:scale-110 transition-transform duration-300">🥭</div>
                        @endif

                        <span class="absolute top-2 left-2 bg-emerald-600 text-white text-[9px] font-bold px-2 py-1 rounded-full shadow">🌞 Today</span>
                        @if($soldOut)
                            <span class="absolute top-2 right-2 bg-stone-700 text-white text-[9px] font-bold px-2 py-1 rounded-full shadow">Sold Out</span>
                        @endif
                    </div>

                    <div class="p-3">
                        <h3 class="font-extrabold text-xs text-stone-800 leading-tight line-clamp-2 mb-1">{{ $product->name }}</h3>
                        <span class="text-amber-600 font-extrabold text-sm">
                            @if($product->activeVariants->isNotEmpty())
                                From ₹{{ number_format($product->activeVariants->min('price'), 2) }}
                            @else
                                From ₹{{ number_format($product->base_price, 2) }}
                            @endif
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- CATEGORIES --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <section class="max-w-7xl mx-auto px-4 py-12">
        <div class="text-center mb-8">
            <span class="text-xs font-bold text-brand-green-dark uppercase tracking-widest">Mukkani & More</span>
            <h2 class="text-3xl md:text-4xl font-extrabold text-brand-green-dark mt-1">Fruits & Farm Products</h2>
            <p class="text-stone-500 mt-2">Grown on our own fields in Bodinayakanur — 100% natural, zero artificial ingredients</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            @foreach([
                [
                    'emoji' => '🥭',
                    'name'  => 'Imam Pasand Mango',
                    'sub'   => 'King of Mangoes',
                    'from'  => '#fef9c3', 'to' => '#fef08a',
                    'ring'  => 'ring-yellow-300',
                    'badge' => 'bg-yellow-100 text-yellow-700',
                    'label' => 'Mukkani',
                ],
                [
                    'emoji' => '🍌',
                    'name'  => 'Red Banana',
                    'sub'   => 'Sweet & Nutritious',
                    'from'  => '#fce7f3', 'to' => '#fbcfe8',
                    'ring'  => 'ring-pink-300',
                    'badge' => 'bg-pink-100 text-pink-700',
                    'label' => 'Mukkani',
                ],
                [
                    'emoji' => '🍈',
                    'name'  => 'Vietnam Early Gold Jackfruit',
                    'sub'   => 'Golden Flesh, Sweet Aroma',
                    'from'  => '#d1fae5', 'to' => '#a7f3d0',
                    'ring'  => 'ring-emerald-300',
                    'badge' => 'bg-emerald-100 text-emerald-700',
                    'label' => 'Mukkani',
                ],
                [
                    'emoji' => '🍉',
                    'name'  => 'Seasonal Fruits',
                    'sub'   => 'Whatever\'s in season, fresh',
                    'from'  => '#fee2e2', 'to' => '#fecaca',
                    'ring'  => 'ring-red-300',
                    'badge' => 'bg-red-100 text-red-700',
                    'label' => 'Mukkani',
                ],
                [
                    'emoji' => '🍊',
                    'name'  => 'Orange Squash',
                    'sub'   => 'No Artificial Colour',
                    'from'  => '#ffedd5', 'to' => '#fed7aa',
                    'ring'  => 'ring-orange-300',
                    'badge' => 'bg-orange-100 text-orange-700',
                    'label' => 'Farm Made',
                ],
                [
                    'emoji' => '🍯',
                    'name'  => 'Mango Jam',
                    'sub'   => 'Pure Tropical Sweetness',
                    'from'  => '#fef3c7', 'to' => '#fde68a',
                    'ring'  => 'ring-amber-300',
                    'badge' => 'bg-amber-100 text-amber-700',
                    'label' => 'Farm Made',
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
    {{-- FEATURED PRODUCTS / BEST SELLERS --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($featured->isNotEmpty())
    <section class="max-w-7xl mx-auto px-4 py-12">
        <div class="flex items-end justify-between mb-8">
            <div>
                <span class="text-xs font-bold text-brand-green-dark uppercase tracking-widest">Editor's Pick</span>
                <h2 class="text-3xl md:text-4xl font-extrabold text-brand-green-dark mt-1">Best Sellers</h2>
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
                @php
                    $lowStockVariant = $product->activeVariants->where('stock_qty', '>', 0)->where('stock_qty', '<=', 5)->first();
                    $soldOut = $product->activeVariants->where('stock_qty', '>', 0)->isEmpty();
                    $thumbUrl = $product->getFirstMediaUrl('thumbnail', 'thumb') ?: $product->getFirstMediaUrl('images', 'thumb');
                @endphp
                <a href="{{ route('products.show', $product->slug) }}"
                   class="group bg-white rounded-3xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-amber-100">

                    <div class="relative aspect-square overflow-hidden" style="background: linear-gradient(135deg, #fef9c3, #fef3c7);">
                        @if($thumbUrl)
                            <img src="{{ $thumbUrl }}"
                                 alt="{{ $product->name }}"
                                 loading="lazy"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <div class="w-full h-full items-center justify-center text-7xl group-hover:scale-110 transition-transform duration-300" style="display:none">🥭</div>
                        @else
                            <div class="w-full h-full flex items-center justify-center text-7xl group-hover:scale-110 transition-transform duration-300">🥭</div>
                        @endif

                        {{-- Badges --}}
                        <div class="absolute top-3 left-3 flex flex-col gap-1">
                            <span class="bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[10px] font-bold px-2.5 py-1 rounded-full shadow">⭐ Featured</span>
                            @if($soldOut)
                                <span class="bg-stone-700 text-white text-[10px] font-bold px-2.5 py-1 rounded-full shadow">Sold Out</span>
                            @elseif($lowStockVariant)
                                <span class="bg-red-500 text-white text-[10px] font-bold px-2.5 py-1 rounded-full shadow animate-pulse">🔥 Only {{ $lowStockVariant->stock_qty }} left!</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-4">
                        <p class="text-[10px] text-amber-600 font-bold uppercase tracking-wider mb-1">{{ $product->category?->name }}</p>
                        <h3 class="font-extrabold text-sm text-stone-800 leading-tight line-clamp-2 mb-2">{{ $product->name }}</h3>

                        {{-- Weight & delivery badges --}}
                        <div class="flex items-center gap-1.5 flex-wrap mb-2">
                            @if($product->active_variants_count > 1)
                                <span class="text-[9px] font-bold text-stone-600 bg-stone-100 px-2 py-0.5 rounded-full">⚖️ {{ $product->active_variants_count }} sizes</span>
                            @elseif($product->activeVariants->isNotEmpty())
                                @php $v = $product->activeVariants->first(); @endphp
                                <span class="text-[9px] font-bold text-stone-600 bg-stone-100 px-2 py-0.5 rounded-full">⚖️ {{ rtrim(rtrim(number_format($v->weight_value, 2), '0'), '.') }}{{ $v->weight_unit }}</span>
                            @endif
                            <span class="text-[9px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">⚡ Fast Delivery</span>
                            @if($product->activeVariants->contains(fn ($v) => filled($v->free_gift_label)))
                                <span class="text-[9px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">🎁 Free Gift</span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between gap-2">
                            <span class="text-amber-600 font-extrabold text-base">
                                @if($product->activeVariants->isNotEmpty())
                                    From ₹{{ number_format($product->activeVariants->min('price'), 2) }}
                                @else
                                    ₹{{ number_format($product->base_price, 2) }}
                                @endif
                            </span>
                            <span class="flex-shrink-0 inline-flex items-center gap-1 bg-amber-500 group-hover:bg-orange-500 text-white text-xs font-bold px-3 py-2 rounded-xl shadow transition-colors">
                                Order Now
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
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
    {{-- WHY CUSTOMERS CHOOSE US --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <section class="py-12 bg-brand-green-light/40">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-10">
                <span class="text-xs font-bold text-brand-green-dark uppercase tracking-widest">Why Merza</span>
                <h2 class="text-3xl font-extrabold text-brand-green-dark mt-1">Why Customers Choose Us</h2>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-5 gap-6 mb-10">
                @foreach([
                    ['🌿', 'Freshly Sourced'],
                    ['📦', 'Carefully Packed'],
                    ['✅', 'Quality Checked'],
                    ['🔒', 'Secure Payment'],
                    ['💬', 'Customer Support'],
                ] as [$icon, $label])
                    <div class="flex flex-col items-center text-center group">
                        <div class="w-14 h-14 rounded-2xl bg-white text-2xl flex items-center justify-center mb-3 shadow-md group-hover:scale-110 transition-transform duration-300">
                            {{ $icon }}
                        </div>
                        <h3 class="font-extrabold text-stone-800 text-sm">{{ $label }}</h3>
                    </div>
                @endforeach
            </div>

            {{-- Real stats --}}
            <div class="flex flex-wrap items-center justify-center gap-8">
                <div class="text-center">
                    <p class="text-3xl font-extrabold text-emerald-700">3,500+</p>
                    <p class="text-xs font-semibold text-stone-500 uppercase tracking-wide">Happy Customers</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-extrabold text-emerald-700">2,000+</p>
                    <p class="text-xs font-semibold text-stone-500 uppercase tracking-wide">Orders Delivered</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- TESTIMONIALS (only real, admin-added reviews) --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($testimonials->isNotEmpty())
    <section class="bg-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-10">
                <span class="text-xs font-bold text-brand-green-dark uppercase tracking-widest">Customer Love</span>
                <h2 class="text-3xl font-extrabold text-brand-green-dark mt-1">What Our Customers Say</h2>
            </div>

            <div class="grid md:grid-cols-3 gap-5">
                @foreach($testimonials as $review)
                    <div class="bg-amber-50 border border-amber-100 rounded-3xl p-6 relative">
                        <div class="text-4xl text-amber-200 font-extrabold leading-none mb-2 select-none">"</div>

                        <p class="text-stone-700 text-sm leading-relaxed mb-5">{{ $review->quote }}</p>

                        <div class="flex items-center gap-0.5 mb-4">
                            @for($i=0;$i<$review->rating;$i++)
                                <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @endfor
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-full bg-amber-200 flex items-center justify-center text-xl flex-shrink-0">🙂</span>
                            <div>
                                <p class="font-extrabold text-sm text-stone-800">{{ $review->customer_name }}</p>
                                @if($review->location)
                                    <p class="text-xs text-stone-400">{{ $review->location }}</p>
                                @endif
                            </div>
                        </div>

                        @if($review->product_tag)
                            <span class="absolute top-5 right-5 text-[10px] font-bold bg-white border border-amber-200 text-amber-700 px-2 py-0.5 rounded-full">{{ $review->product_tag }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- DELIVERY INFO --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($deliveryZones->isNotEmpty())
    <section class="bg-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-10">
                <span class="text-xs font-bold text-brand-green-dark uppercase tracking-widest">Where We Deliver</span>
                <h2 class="text-3xl font-extrabold text-brand-green-dark mt-1">Delivery Information</h2>
            </div>

            <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4">
                @foreach($deliveryZones as $zone)
                    <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-5 flex items-center justify-between">
                        <div>
                            <p class="font-extrabold text-sm text-stone-800">{{ $zone->name }}</p>
                            <p class="text-xs text-stone-500 mt-0.5">₹{{ number_format($zone->rate_per_kg, 0) }}/kg</p>
                        </div>
                        <span class="text-xs font-bold text-emerald-700 bg-white px-2.5 py-1 rounded-full border border-emerald-200 flex-shrink-0">
                            🚚 {{ $zone->eta_days ?? 2 }} day{{ ($zone->eta_days ?? 2) > 1 ? 's' : '' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- FAQ --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <section class="bg-white py-12">
        <div class="max-w-3xl mx-auto px-4">
            <div class="text-center mb-10">
                <span class="text-xs font-bold text-brand-green-dark uppercase tracking-widest">Got Questions?</span>
                <h2 class="text-3xl font-extrabold text-brand-green-dark mt-1">Frequently Asked Questions</h2>
            </div>

            <div class="space-y-3">
                @foreach([
                    ['Which areas do you deliver to?', 'We deliver across Tamil Nadu and to select cities nationwide — see the Delivery Information section above for zones and rates, or message us on WhatsApp to check your area.'],
                    ['What payment methods do you accept?', 'UPI (Google Pay, PhonePe, or any UPI app via QR code) and Cash on Delivery, where available. Card payments are coming soon.'],
                    ['Are your fruits naturally ripened?', 'Yes — all our fruits are grown on our own farm in Bodinayakanur and naturally ripened, with no artificial ripening agents or added colours.'],
                    ['Is there a minimum order or bulk pricing?', 'No minimum order for regular orders. For bulk or B2B orders, message us on WhatsApp for wholesale pricing.'],
                    ['What if my fruits arrive damaged?', 'Message us on WhatsApp with a photo within 24 hours of delivery and we\'ll arrange a replacement or refund.'],
                    ['How do I track my order?', 'We\'ll send updates and confirm delivery details on WhatsApp — you can also message us anytime with your order number.'],
                ] as [$q, $a])
                    <details class="group bg-amber-50 border border-amber-100 rounded-2xl p-5">
                        <summary class="flex items-center justify-between cursor-pointer font-extrabold text-sm text-stone-800 list-none">
                            {{ $q }}
                            <svg class="w-4 h-4 text-amber-500 transition-transform group-open:rotate-45" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                        </summary>
                        <p class="text-sm text-stone-600 mt-3 leading-relaxed">{{ $a }}</p>
                    </details>
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
                    <a href="https://wa.me/919360064278?text=Hi+Merza%2C+I+want+to+place+an+order!" target="_blank"
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
