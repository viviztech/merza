<div class="max-w-6xl mx-auto px-4 py-6 md:py-10">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-stone-400 mb-6 flex-wrap">
        <a href="{{ route('home') }}" class="hover:text-amber-600 transition-colors font-medium">Home</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('products.index') }}" class="hover:text-amber-600 transition-colors font-medium">Products</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-stone-600 font-semibold">{{ $product->name }}</span>
    </nav>

    <div class="grid md:grid-cols-2 gap-8 md:gap-12">

        {{-- ── Left: Image gallery ── --}}
        <div class="space-y-3">
            <div class="aspect-square rounded-3xl overflow-hidden border-2 border-amber-100 shadow-lg"
                 style="background: linear-gradient(145deg, #fef9c3, #fef3c7);">
                @if($product->getFirstMediaUrl('thumbnail', 'card'))
                    <img src="{{ $product->getFirstMediaUrl('thumbnail', 'card') }}"
                         alt="{{ $product->name }}"
                         class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center text-[8rem] float-fruit">🥭</div>
                @endif
            </div>

            @if($product->getMedia('images')->count() > 1)
                <div class="flex gap-2 overflow-x-auto pb-1">
                    @foreach($product->getMedia('images') as $media)
                        <img src="{{ $media->getUrl('thumb') }}"
                             alt="{{ $product->name }}"
                             class="w-16 h-16 object-cover rounded-xl border-2 border-transparent hover:border-amber-400 cursor-pointer flex-shrink-0 transition-all">
                    @endforeach
                </div>
            @endif

            {{-- Trust mini-badges --}}
            <div class="grid grid-cols-3 gap-2">
                @foreach([
                    ['🌿', 'Farm Fresh'],
                    ['📦', 'Careful Packing'],
                    ['🚚', 'Fast Delivery'],
                ] as [$icon, $label])
                    <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-2 text-center">
                        <span class="text-lg">{{ $icon }}</span>
                        <p class="text-[10px] font-semibold text-emerald-700 mt-0.5">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ── Right: Product info ── --}}
        <div>
            {{-- Category + badges --}}
            <div class="flex items-center gap-2 mb-3">
                @if($product->category)
                    <span class="text-xs font-extrabold text-amber-600 uppercase tracking-widest">{{ $product->category->name }}</span>
                @endif
                @if($product->is_featured)
                    <span class="bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">⭐ Featured</span>
                @endif
            </div>

            <h1 class="text-2xl md:text-3xl font-extrabold text-stone-900 leading-tight mb-2">{{ $product->name }}</h1>

            @if($product->short_description)
                <p class="text-stone-500 text-sm mb-5 leading-relaxed">{{ $product->short_description }}</p>
            @endif

            {{-- Price --}}
            <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-100 rounded-2xl p-4 mb-5">
                @if($selectedVariant)
                    <div class="flex items-end gap-2">
                        <span class="text-4xl font-extrabold text-amber-600">RM{{ number_format($selectedVariant->price, 2) }}</span>
                        <span class="text-sm text-stone-400 pb-1">per {{ $selectedVariant->weight_value }}{{ $selectedVariant->weight_unit }}</span>
                    </div>
                @else
                    <span class="text-2xl font-extrabold text-amber-600">Select a size</span>
                @endif

                {{-- Stock status --}}
                @if($selectedVariant)
                    @if($selectedVariant->stock_qty <= 0)
                        <div class="flex items-center gap-1.5 mt-2">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            <span class="text-sm font-semibold text-red-600">Out of stock</span>
                        </div>
                    @elseif($selectedVariant->stock_qty <= $selectedVariant->low_stock_threshold)
                        <div class="flex items-center gap-1.5 mt-2">
                            <span class="w-2 h-2 rounded-full bg-orange-500 pulse-dot"></span>
                            <span class="text-sm font-semibold text-orange-600">Only {{ $selectedVariant->stock_qty }} left — order soon!</span>
                        </div>
                    @else
                        <div class="flex items-center gap-1.5 mt-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 pulse-dot"></span>
                            <span class="text-sm font-semibold text-emerald-600">In stock · Ready to ship</span>
                        </div>
                    @endif
                @endif
            </div>

            {{-- Variant selector --}}
            @if($product->activeVariants->isNotEmpty())
                <div class="mb-5">
                    <p class="text-sm font-extrabold text-stone-700 mb-3">Choose Size / Weight</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($product->activeVariants as $variant)
                            <button wire:click="$set('selectedVariantId', {{ $variant->id }})"
                                    class="group px-4 py-2.5 rounded-2xl border-2 font-bold transition-all text-sm
                                           {{ $selectedVariantId == $variant->id
                                              ? 'border-amber-500 bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-md shadow-amber-200'
                                              : 'border-stone-200 text-stone-700 hover:border-amber-300 hover:bg-amber-50' }}">
                                {{ $variant->name }}
                                <span class="block text-xs font-medium {{ $selectedVariantId == $variant->id ? 'text-amber-100' : 'text-stone-400' }}">
                                    RM{{ number_format($variant->price, 2) }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Quantity stepper --}}
            <div class="mb-6">
                <p class="text-sm font-extrabold text-stone-700 mb-3">Quantity</p>
                <div class="flex items-center gap-0 bg-stone-100 rounded-2xl w-fit overflow-hidden border border-stone-200">
                    <button wire:click="$set('qty', {{ max(1, $qty - 1) }})"
                            class="w-11 h-11 flex items-center justify-center text-stone-600 hover:bg-amber-100 hover:text-amber-700 transition-colors font-extrabold text-xl">−</button>
                    <span class="w-12 text-center font-extrabold text-lg text-stone-800">{{ $qty }}</span>
                    <button wire:click="$set('qty', {{ $qty + 1 }})"
                            class="w-11 h-11 flex items-center justify-center text-stone-600 hover:bg-amber-100 hover:text-amber-700 transition-colors font-extrabold text-xl">+</button>
                </div>
            </div>

            {{-- CTA Buttons --}}
            <div class="flex flex-col gap-3 mb-5">
                <button wire:click="addToCart"
                        wire:loading.attr="disabled"
                        @if($selectedVariant?->stock_qty <= 0) disabled @endif
                        class="w-full bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 disabled:opacity-50 disabled:cursor-not-allowed text-white font-extrabold py-4 px-6 rounded-2xl flex items-center justify-center gap-2 transition-all shadow-lg shadow-amber-200/50 hover:shadow-xl hover:-translate-y-0.5 text-base">
                    <span wire:loading.remove wire:target="addToCart" class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Add to Cart
                    </span>
                    <span wire:loading wire:target="addToCart" class="flex items-center gap-2">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Adding…
                    </span>
                </button>

                <a href="https://wa.me/60123456789?text=Hi%2C+I+want+to+order+{{ urlencode($product->name . ($selectedVariant ? ' - ' . $selectedVariant->name : '')) }}"
                   target="_blank"
                   class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold py-4 px-6 rounded-2xl flex items-center justify-center gap-2 transition-all text-base shadow-sm hover:shadow-md">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    Order via WhatsApp
                </a>
            </div>

            {{-- Success flash --}}
            @if($addedMessage)
                <div x-data="{ show: true }"
                     x-init="setTimeout(() => show = false, 3500)"
                     x-show="show"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-emerald-800">{{ $addedMessage }}</p>
                        <a href="{{ route('cart.index') }}" class="text-xs text-emerald-600 underline font-medium">View Cart & Checkout →</a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Description --}}
    @if($product->description)
        <div class="mt-10 bg-white rounded-3xl border border-amber-100 shadow-sm overflow-hidden">
            <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-b border-amber-100 px-6 py-4">
                <h2 class="font-extrabold text-stone-800">About This Product</h2>
            </div>
            <div class="p-6 prose prose-sm text-stone-600 max-w-none prose-headings:font-extrabold prose-headings:text-stone-800 prose-a:text-amber-600">
                {!! $product->description !!}
            </div>
        </div>
    @endif

    {{-- Mobile sticky CTA --}}
    <div class="md:hidden fixed bottom-20 left-0 right-0 z-30 px-4 pb-2">
        <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-amber-100 shadow-xl p-3 flex gap-3">
            <button wire:click="addToCart"
                    wire:loading.attr="disabled"
                    @if($selectedVariant?->stock_qty <= 0) disabled @endif
                    class="flex-1 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-extrabold py-3 rounded-xl text-sm flex items-center justify-center gap-2 shadow disabled:opacity-50">
                <span wire:loading.remove wire:target="addToCart">🛒 Add to Cart</span>
                <span wire:loading wire:target="addToCart">Adding…</span>
            </button>
            <a href="https://wa.me/60123456789?text=Hi%2C+I+want+to+order+{{ urlencode($product->name) }}"
               target="_blank"
               class="px-4 py-3 rounded-xl bg-emerald-600 text-white font-bold text-sm flex items-center justify-center">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
            </a>
        </div>
    </div>
</div>
