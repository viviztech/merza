<div x-data="{ filtersOpen: false }">

    {{-- Sticky top bar: search + mobile filter trigger --}}
    <div class="bg-white/95 backdrop-blur-sm border-b border-amber-100 sticky top-16 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-3">

            {{-- Search --}}
            <div class="relative flex-1">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z"/>
                </svg>
                <input wire:model.live.debounce.300ms="search"
                       type="search"
                       placeholder="Search mangoes, jackfruit…"
                       class="w-full pl-10 pr-4 py-2.5 text-sm bg-amber-50 border border-amber-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-400 focus:bg-white transition-all placeholder-stone-400">
            </div>

            {{-- Mobile filter trigger --}}
            <button @click="filtersOpen = true"
                    class="md:hidden flex-shrink-0 relative inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border-2 border-amber-200 bg-amber-50 text-stone-700 text-sm font-bold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 8h12M9 12h6M11 16h2"/>
                </svg>
                Filters
                @if($search || $categorySlug || $priceMin || $priceMax || $inStockOnly)
                    <span class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full bg-amber-500 text-white text-[9px] font-bold flex items-center justify-center">•</span>
                @endif
            </button>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-6 md:py-8 flex flex-col md:flex-row gap-6 items-start">

        {{-- ═══════════════════════════════════════════════ --}}
        {{-- FILTERS SIDEBAR (static on desktop, slide-in drawer on mobile) --}}
        {{-- ═══════════════════════════════════════════════ --}}
        <aside :class="filtersOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
               class="fixed md:sticky inset-y-0 left-0 md:left-auto md:top-32 z-50 md:z-auto w-80 max-w-[85vw] md:w-64 lg:w-72 shrink-0 min-w-0
                      bg-white md:bg-transparent h-full md:h-auto overflow-y-auto md:overflow-visible
                      transform transition-transform duration-300 ease-out md:transition-none">

            <div class="p-5 md:p-0">
                {{-- Mobile drawer header --}}
                <div class="flex items-center justify-between mb-5 md:hidden">
                    <h2 class="font-extrabold text-stone-800 text-lg">Filters</h2>
                    <button @click="filtersOpen = false" class="w-9 h-9 flex items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="bg-white md:border md:border-amber-100 md:rounded-3xl md:p-5 space-y-6">

                    {{-- Header + clear (desktop) --}}
                    <div class="hidden md:flex items-center justify-between">
                        <h2 class="font-extrabold text-stone-800">Filters</h2>
                        @if($search || $categorySlug || $priceMin || $priceMax || $inStockOnly)
                            <button wire:click="clearFilters" class="text-xs font-bold text-amber-600 hover:text-amber-700">
                                Clear all ✕
                            </button>
                        @endif
                    </div>

                    {{-- Category --}}
                    <div>
                        <p class="text-[11px] font-bold text-stone-400 uppercase tracking-wide mb-3">Category</p>
                        <div class="space-y-1">
                            <button wire:click="$set('categorySlug','')"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-semibold text-left transition-all
                                           {{ $categorySlug === ''
                                              ? 'bg-amber-50 text-amber-700'
                                              : 'text-stone-600 hover:bg-stone-50' }}">
                                <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $categorySlug === '' ? 'bg-amber-500' : 'bg-stone-200' }}"></span>
                                All Fruits
                            </button>
                            @foreach($categories as $cat)
                                <button wire:click="$set('categorySlug','{{ $cat->slug }}')"
                                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-semibold text-left transition-all
                                               {{ $categorySlug === $cat->slug
                                                  ? 'bg-amber-50 text-amber-700'
                                                  : 'text-stone-600 hover:bg-stone-50' }}">
                                    <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $categorySlug === $cat->slug ? 'bg-amber-500' : 'bg-stone-200' }}"></span>
                                    {{ $cat->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="border-t border-stone-100"></div>

                    {{-- Price Range --}}
                    <div>
                        <p class="text-[11px] font-bold text-stone-400 uppercase tracking-wide mb-3">Price Range</p>
                        <div class="flex items-center gap-2">
                            <input wire:model.live.debounce.500ms="priceMin" type="number" min="0" placeholder="Min ₹"
                                   class="w-full min-w-0 px-3 py-2 text-sm bg-amber-50 border border-amber-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-400 focus:bg-white transition-all">
                            <span class="text-stone-300 text-xs flex-shrink-0">–</span>
                            <input wire:model.live.debounce.500ms="priceMax" type="number" min="0" placeholder="Max ₹"
                                   class="w-full min-w-0 px-3 py-2 text-sm bg-amber-50 border border-amber-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-400 focus:bg-white transition-all">
                        </div>
                    </div>

                    <div class="border-t border-stone-100"></div>

                    {{-- Availability --}}
                    <div>
                        <p class="text-[11px] font-bold text-stone-400 uppercase tracking-wide mb-3">Availability</p>
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input wire:model.live="inStockOnly" type="checkbox"
                                   class="w-4 h-4 rounded text-amber-500 border-amber-300 focus:ring-amber-400">
                            <span class="text-sm font-semibold text-stone-600">In stock only</span>
                        </label>
                    </div>

                    {{-- Clear (mobile) --}}
                    @if($search || $categorySlug || $priceMin || $priceMax || $inStockOnly)
                        <button wire:click="clearFilters" class="md:hidden w-full text-center text-sm font-bold text-amber-600 hover:text-amber-700 border-t border-stone-100 pt-4">
                            Clear all filters ✕
                        </button>
                    @endif
                </div>

                {{-- Apply button (mobile only, closes drawer) --}}
                <button @click="filtersOpen = false"
                        class="md:hidden w-full mt-4 bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 rounded-2xl transition-colors">
                    Show {{ $products->total() }} {{ Str::plural('result', $products->total()) }}
                </button>
            </div>
        </aside>

        {{-- Backdrop (mobile only) --}}
        <div x-show="filtersOpen" x-cloak
             x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="filtersOpen = false"
             class="fixed inset-0 bg-stone-900/50 z-40 md:hidden"></div>

        {{-- ═══════════════════════════════════════════════ --}}
        {{-- PRODUCT LIST --}}
        {{-- ═══════════════════════════════════════════════ --}}
        <div class="flex-1 min-w-0 w-full">

            @if($products->isEmpty())
                {{-- Empty state --}}
                <div class="text-center py-24">
                    <div class="text-7xl mb-5 float-fruit inline-block">🥭</div>
                    <h3 class="text-xl font-extrabold text-stone-700 mb-2">No fruits found</h3>
                    <p class="text-stone-400 text-sm mb-5">Try a different search term or browse all products</p>
                    <button wire:click="clearFilters"
                            class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-bold px-6 py-3 rounded-2xl transition-colors">
                        Clear filters
                    </button>
                </div>
            @else
                {{-- Results count --}}
                <div class="flex items-center justify-between mb-5">
                    <p class="text-sm text-stone-500">
                        <span class="font-bold text-stone-700">{{ $products->total() }}</span> fruits found
                        @if($search) for "<span class="text-amber-600 font-medium">{{ $search }}</span>" @endif
                    </p>
                </div>

                {{-- Product grid --}}
                <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-5">
                    @foreach($products as $product)
                        <a href="{{ route('products.show', $product->slug) }}"
                           class="group bg-white rounded-3xl overflow-hidden border border-amber-100 hover:border-amber-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col">

                            {{-- Image --}}
                            <div class="relative aspect-square overflow-hidden"
                                 style="background: linear-gradient(145deg, #fef9c3, #fef3c7);">
                                @php $thumbUrl = $product->getFirstMediaUrl('thumbnail', 'thumb') ?: $product->getFirstMediaUrl('images', 'thumb'); @endphp
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

                                {{-- Badges --}}
                                <div class="absolute top-2.5 left-2.5 flex flex-col gap-1">
                                    @if($product->is_featured)
                                        <span class="bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[9px] font-extrabold px-2 py-0.5 rounded-full shadow">⭐ Featured</span>
                                    @endif
                                    @if($product->is_preorder)
                                        <span class="bg-emerald-700 text-white text-[9px] font-extrabold px-2 py-0.5 rounded-full shadow">Pre-book now</span>
                                    @endif
                                    @php
                                        $lowQty = $product->activeVariants->where('stock_qty', '>', 0)->where('stock_qty', '<=', 5)->min('stock_qty');
                                    @endphp
                                    @if($product->activeVariants->isNotEmpty() && $product->activeVariants->where('stock_qty', '>', 0)->isEmpty())
                                        <span class="bg-stone-700 text-white text-[9px] font-bold px-2 py-0.5 rounded-full">Sold Out</span>
                                    @elseif($lowQty)
                                        <span class="bg-red-500 text-white text-[9px] font-bold px-2 py-0.5 rounded-full animate-pulse">🔥 Only {{ $lowQty }} left!</span>
                                    @endif
                                </div>

                                {{-- Quick view overlay --}}
                                <div class="absolute inset-0 bg-gradient-to-t from-stone-900/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end justify-center pb-4">
                                    <span class="bg-white text-stone-800 text-xs font-bold px-4 py-1.5 rounded-xl shadow">View Details</span>
                                </div>
                            </div>

                            {{-- Info --}}
                            <div class="p-3.5 flex-1 flex flex-col">
                                <p class="text-[10px] text-amber-600 font-bold uppercase tracking-wider mb-1">{{ $product->category?->name }}</p>
                                <h3 class="font-extrabold text-sm text-stone-800 leading-tight line-clamp-2 mb-1">{{ $product->name }}</h3>
                                <p class="text-xs text-stone-400 line-clamp-1 mb-2">{{ $product->short_description }}</p>

                                @if($product->is_preorder && $product->available_from)
                                    <div class="mb-2">
                                        <span class="text-[9px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">Dispatches {{ $product->available_from->format('d M') }}</span>
                                    </div>
                                @endif

                                <div class="mt-auto pt-2">
                                    <span class="block text-amber-600 font-extrabold text-base mb-2">
                                        @if($product->min_price_per_kg)
                                            ₹{{ number_format($product->min_price_per_kg, 2) }}/kg
                                        @elseif($product->activeVariants->isNotEmpty())
                                            ₹{{ number_format($product->activeVariants->min('price'), 2) }}
                                        @else
                                            ₹{{ number_format($product->base_price, 2) }}
                                        @endif
                                    </span>
                                    <span class="w-full inline-flex items-center justify-center gap-1 bg-amber-500 group-hover:bg-orange-500 text-white text-xs font-bold px-3 py-2 rounded-xl shadow transition-colors">
                                        {{ $product->is_preorder ? 'Pre-book now' : 'Order Now' }}
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-10">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
