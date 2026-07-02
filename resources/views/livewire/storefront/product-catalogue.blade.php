<div>
    {{-- Toolbar --}}
    <div class="bg-white border-b sticky top-16 z-30">
        <div class="max-w-7xl mx-auto px-4 py-3 flex flex-col sm:flex-row gap-3 items-start sm:items-center">
            {{-- Search --}}
            <div class="relative flex-1 w-full sm:max-w-xs">
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z"/>
                </svg>
                <input wire:model.live.debounce.300ms="search"
                       type="search"
                       placeholder="Search products…"
                       class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            {{-- Category pills --}}
            <div class="flex gap-2 overflow-x-auto pb-1 sm:pb-0 flex-nowrap">
                <button wire:click="$set('categorySlug','')"
                        class="flex-shrink-0 px-3 py-1.5 text-xs font-medium rounded-full transition-colors
                               {{ $categorySlug === '' ? 'bg-green-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    All
                </button>
                @foreach($categories as $cat)
                    <button wire:click="$set('categorySlug','{{ $cat->slug }}')"
                            class="flex-shrink-0 px-3 py-1.5 text-xs font-medium rounded-full transition-colors
                                   {{ $categorySlug === $cat->slug ? 'bg-green-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        {{ $cat->name }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Product grid --}}
    <div class="max-w-7xl mx-auto px-4 py-8">
        @if($products->isEmpty())
            <div class="text-center py-20 text-gray-400">
                <div class="text-5xl mb-4">🥭</div>
                <p class="text-lg font-medium">No products found</p>
                <button wire:click="$set('search','')" class="mt-3 text-green-700 text-sm underline">Clear search</button>
            </div>
        @else
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($products as $product)
                    <a href="{{ route('products.show', $product->slug) }}"
                       class="group bg-white rounded-2xl border border-gray-100 hover:shadow-lg hover:-translate-y-1 transition-all duration-200 overflow-hidden flex flex-col">

                        {{-- Image --}}
                        <div class="relative aspect-square bg-gradient-to-br from-green-50 to-lime-50 overflow-hidden">
                            @if($product->getFirstMediaUrl('thumbnail','thumb'))
                                <img src="{{ $product->getFirstMediaUrl('thumbnail','thumb') }}"
                                     alt="{{ $product->name }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-6xl">🥭</div>
                            @endif

                            @if($product->is_featured)
                                <span class="absolute top-2 left-2 bg-yellow-400 text-yellow-900 text-xs font-bold px-2 py-0.5 rounded-full">Featured</span>
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="p-3 flex-1 flex flex-col">
                            <p class="text-xs text-gray-400 mb-0.5">{{ $product->category?->name }}</p>
                            <h3 class="font-semibold text-sm text-gray-800 leading-tight line-clamp-2">{{ $product->name }}</h3>
                            <p class="text-xs text-gray-500 mt-1 line-clamp-2 flex-1">{{ $product->short_description }}</p>

                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-green-700 font-bold text-sm">
                                    @if($product->activeVariants->isNotEmpty())
                                        From RM{{ number_format($product->activeVariants->min('price'), 2) }}
                                    @else
                                        RM{{ number_format($product->base_price, 2) }}
                                    @endif
                                </span>
                                <span class="text-xs text-gray-400">{{ $product->active_variants_count }} options</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
