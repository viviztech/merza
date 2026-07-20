<div>
    {{-- Sticky toolbar --}}
    <div class="bg-white/95 backdrop-blur-sm border-b border-amber-100 sticky top-16 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">

                {{-- Search --}}
                <div class="relative flex-1 w-full sm:max-w-sm">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="search"
                           type="search"
                           placeholder="Search mangoes, jackfruit…"
                           class="w-full pl-10 pr-4 py-2.5 text-sm bg-amber-50 border border-amber-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-400 focus:bg-white transition-all placeholder-stone-400">
                </div>

                {{-- Category pills --}}
                <div class="flex gap-2 overflow-x-auto pb-0.5 flex-nowrap scrollbar-none">
                    <button wire:click="$set('categorySlug','')"
                            class="flex-shrink-0 px-4 py-2 text-xs font-bold rounded-xl transition-all
                                   {{ $categorySlug === ''
                                      ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-sm shadow-amber-200'
                                      : 'bg-amber-50 text-stone-600 hover:bg-amber-100 border border-amber-200' }}">
                        All Fruits
                    </button>
                    @foreach($categories as $cat)
                        <button wire:click="$set('categorySlug','{{ $cat->slug }}')"
                                class="flex-shrink-0 px-4 py-2 text-xs font-bold rounded-xl transition-all
                                       {{ $categorySlug === $cat->slug
                                          ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-sm shadow-amber-200'
                                          : 'bg-amber-50 text-stone-600 hover:bg-amber-100 border border-amber-200' }}">
                            {{ $cat->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Price range + availability --}}
            <div class="flex flex-wrap items-center gap-3 mt-3">
                <span class="text-[11px] font-bold text-stone-400 uppercase tracking-wide">Price</span>
                <input wire:model.live.debounce.500ms="priceMin" type="number" min="0" placeholder="Min ₹"
                       class="w-24 px-3 py-1.5 text-xs bg-amber-50 border border-amber-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-400 focus:bg-white transition-all">
                <span class="text-stone-300 text-xs">–</span>
                <input wire:model.live.debounce.500ms="priceMax" type="number" min="0" placeholder="Max ₹"
                       class="w-24 px-3 py-1.5 text-xs bg-amber-50 border border-amber-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-400 focus:bg-white transition-all">

                <label class="flex items-center gap-1.5 ml-2 cursor-pointer">
                    <input wire:model.live="inStockOnly" type="checkbox"
                           class="w-4 h-4 rounded text-amber-500 border-amber-300 focus:ring-amber-400">
                    <span class="text-xs font-semibold text-stone-600">In stock only</span>
                </label>

                @if($search || $categorySlug || $priceMin || $priceMax || $inStockOnly)
                    <button wire:click="clearFilters" class="text-xs font-bold text-amber-600 hover:text-amber-700 ml-auto">
                        Clear filters ✕
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-8">

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
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-5">
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

                            <div class="flex items-center justify-between mt-auto gap-2">
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

            {{-- Pagination --}}
            <div class="mt-10">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
