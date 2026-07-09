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
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-8">

        @if($products->isEmpty())
            {{-- Empty state --}}
            <div class="text-center py-24">
                <div class="text-7xl mb-5 float-fruit inline-block">🥭</div>
                <h3 class="text-xl font-extrabold text-stone-700 mb-2">No fruits found</h3>
                <p class="text-stone-400 text-sm mb-5">Try a different search term or browse all products</p>
                <button wire:click="$set('search','')" wire:click="$set('categorySlug','')"
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
                                @if($product->activeVariants->isNotEmpty() && $product->activeVariants->where('stock_qty', '>', 0)->count() === 0)
                                    <span class="bg-stone-700 text-white text-[9px] font-bold px-2 py-0.5 rounded-full">Sold Out</span>
                                @elseif($product->activeVariants->isNotEmpty() && $product->activeVariants->where('stock_qty', '<=', 5)->where('stock_qty', '>', 0)->count() > 0)
                                    <span class="bg-red-500 text-white text-[9px] font-bold px-2 py-0.5 rounded-full">Low Stock</span>
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
                            <p class="text-xs text-stone-400 line-clamp-1 flex-1 mb-2">{{ $product->short_description }}</p>

                            <div class="flex items-center justify-between mt-auto">
                                <div>
                                    <span class="text-amber-600 font-extrabold text-base">
                                        @if($product->activeVariants->isNotEmpty())
                                            From ₹{{ number_format($product->activeVariants->min('price'), 2) }}
                                        @else
                                            ₹{{ number_format($product->base_price, 2) }}
                                        @endif
                                    </span>
                                    @if($product->active_variants_count > 1)
                                        <p class="text-[10px] text-stone-400">{{ $product->active_variants_count }} sizes</p>
                                    @endif
                                </div>
                                <span class="w-8 h-8 rounded-xl bg-amber-500 group-hover:bg-orange-500 flex items-center justify-center text-white shadow transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
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
