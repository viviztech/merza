<div class="max-w-5xl mx-auto px-4 py-8">

    {{-- Breadcrumb --}}
    <nav class="text-sm text-gray-400 mb-6 flex items-center gap-2">
        <a href="{{ route('home') }}" class="hover:text-green-700">Home</a>
        <span>/</span>
        <a href="{{ route('products.index') }}" class="hover:text-green-700">Products</a>
        <span>/</span>
        <span class="text-gray-700">{{ $product->name }}</span>
    </nav>

    <div class="grid md:grid-cols-2 gap-8">

        {{-- Image gallery --}}
        <div>
            <div class="aspect-square bg-gradient-to-br from-green-50 to-lime-50 rounded-2xl overflow-hidden mb-3">
                @if($product->getFirstMediaUrl('thumbnail','card'))
                    <img src="{{ $product->getFirstMediaUrl('thumbnail','card') }}"
                         alt="{{ $product->name }}"
                         class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center text-9xl">🥭</div>
                @endif
            </div>

            {{-- Thumbnails --}}
            @if($product->getMedia('images')->count() > 1)
                <div class="flex gap-2 overflow-x-auto">
                    @foreach($product->getMedia('images') as $media)
                        <img src="{{ $media->getUrl('thumb') }}"
                             alt="{{ $product->name }}"
                             class="w-16 h-16 object-cover rounded-lg border-2 border-transparent hover:border-green-500 cursor-pointer flex-shrink-0">
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Product info --}}
        <div>
            @if($product->category)
                <p class="text-xs text-green-700 font-medium uppercase tracking-wider mb-1">{{ $product->category->name }}</p>
            @endif

            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">{{ $product->name }}</h1>

            @if($product->short_description)
                <p class="text-gray-500 text-sm mb-4">{{ $product->short_description }}</p>
            @endif

            {{-- Price --}}
            <div class="mb-5">
                @if($selectedVariant)
                    <span class="text-3xl font-bold text-green-700">RM{{ number_format($selectedVariant->price, 2) }}</span>
                    <span class="text-sm text-gray-400 ml-2">per {{ $selectedVariant->weight_value }}{{ $selectedVariant->weight_unit }}</span>
                @endif
            </div>

            {{-- Variant selector --}}
            @if($product->activeVariants->isNotEmpty())
                <div class="mb-5">
                    <p class="text-sm font-semibold text-gray-700 mb-2">Select Size / Weight</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($product->activeVariants as $variant)
                            <button wire:click="$set('selectedVariantId', {{ $variant->id }})"
                                    class="px-4 py-2 text-sm rounded-xl border-2 font-medium transition-all
                                           {{ $selectedVariantId == $variant->id
                                              ? 'border-green-700 bg-green-700 text-white'
                                              : 'border-gray-200 text-gray-700 hover:border-green-400' }}">
                                {{ $variant->name }}
                                <span class="block text-xs {{ $selectedVariantId == $variant->id ? 'text-green-100' : 'text-gray-400' }}">
                                    RM{{ number_format($variant->price, 2) }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Quantity --}}
            <div class="mb-5">
                <p class="text-sm font-semibold text-gray-700 mb-2">Quantity</p>
                <div class="flex items-center gap-3">
                    <button wire:click="$set('qty', {{ max(1, $qty - 1) }})"
                            class="w-9 h-9 rounded-full border-2 border-gray-200 flex items-center justify-center text-gray-600 hover:border-green-500 transition-colors font-bold">−</button>
                    <span class="w-8 text-center font-semibold text-lg">{{ $qty }}</span>
                    <button wire:click="$set('qty', {{ $qty + 1 }})"
                            class="w-9 h-9 rounded-full border-2 border-gray-200 flex items-center justify-center text-gray-600 hover:border-green-500 transition-colors font-bold">+</button>
                </div>
            </div>

            {{-- Stock badge --}}
            @if($selectedVariant)
                @if($selectedVariant->stock_qty <= 0)
                    <p class="text-red-500 text-sm font-medium mb-4">Out of stock</p>
                @elseif($selectedVariant->stock_qty <= $selectedVariant->low_stock_threshold)
                    <p class="text-orange-500 text-sm font-medium mb-4">Only {{ $selectedVariant->stock_qty }} left!</p>
                @else
                    <p class="text-green-600 text-sm font-medium mb-4">✓ In stock</p>
                @endif
            @endif

            {{-- Add to cart --}}
            <div class="flex flex-col sm:flex-row gap-3">
                <button wire:click="addToCart"
                        wire:loading.attr="disabled"
                        @if($selectedVariant?->stock_qty <= 0) disabled @endif
                        class="flex-1 bg-green-700 hover:bg-green-600 disabled:opacity-50 text-white font-bold py-3 px-6 rounded-xl flex items-center justify-center gap-2 transition-colors">
                    <span wire:loading.remove wire:target="addToCart">
                        🛒 Add to Cart
                    </span>
                    <span wire:loading wire:target="addToCart">Adding…</span>
                </button>

                <a href="https://wa.me/60123456789?text=Hi%2C+I+want+to+order+{{ urlencode($product->name . ($selectedVariant ? ' - ' . $selectedVariant->name : '')) }}"
                   target="_blank"
                   class="flex-1 border-2 border-green-700 text-green-700 hover:bg-green-50 font-bold py-3 px-6 rounded-xl flex items-center justify-center gap-2 transition-colors">
                    💬 Order on WhatsApp
                </a>
            </div>

            {{-- Success flash --}}
            @if($addedMessage)
                <div x-data="{ show: true }"
                     x-init="setTimeout(() => show = false, 3000)"
                     x-show="show"
                     x-transition
                     class="mt-3 bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-2 rounded-lg">
                    ✓ {{ $addedMessage }} — <a href="{{ route('cart.index') }}" class="underline font-medium">View Cart</a>
                </div>
            @endif
        </div>
    </div>

    {{-- Description --}}
    @if($product->description)
        <div class="mt-10 bg-white rounded-2xl border border-gray-100 p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-3">Product Details</h2>
            <div class="prose prose-sm text-gray-600 max-w-none">
                {!! $product->description !!}
            </div>
        </div>
    @endif

</div>
