<x-layouts.storefront :title="$category->name . ' — Shop Online'">
    <main class="max-w-7xl mx-auto px-4 py-10">
        <nav aria-label="Breadcrumb" class="text-sm text-stone-500 mb-6">
            <a href="{{ route('home') }}" class="hover:text-emerald-700">Home</a> /
            <a href="{{ route('products.index') }}" class="hover:text-emerald-700">Products</a> /
            <span class="text-stone-800">{{ $category->name }}</span>
        </nav>

        <header class="max-w-3xl mb-10">
            <h1 class="text-3xl md:text-4xl font-extrabold text-emerald-950">{{ $category->name }}</h1>
            @if($tamilName)<p lang="ta" class="mt-2 text-emerald-800">{{ $tamilName }}</p>@endif
            <p class="mt-3 text-stone-600 leading-relaxed">{{ $categoryIntro }}</p>
        </header>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
            @foreach($products as $product)
                @php $image = $product->getFirstMediaUrl('thumbnail', 'thumb') ?: $product->getFirstMediaUrl('images', 'thumb'); @endphp
                <a href="{{ route('products.show', $product->slug) }}" class="block rounded-2xl overflow-hidden border border-emerald-100 bg-white hover:shadow-lg transition-shadow">
                    <img src="{{ $image ?: asset('images/placeholder-product.png') }}" alt="{{ $product->name }}" loading="lazy" class="aspect-square w-full object-cover">
                    <div class="p-4">
                        <h2 class="font-bold text-stone-800">{{ $product->name }}</h2>
                        @if($product->activeVariants->isNotEmpty())
                            <p class="mt-2 text-amber-700 font-bold">From ₹{{ number_format($product->lowestPricedVariant()->price, 2) }}</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">{{ $products->links() }}</div>

        <section class="mt-14 max-w-3xl border-t border-emerald-100 pt-8" aria-labelledby="category-questions">
            <h2 id="category-questions" class="text-xl font-extrabold text-emerald-950">Shopping this category</h2>
            <h3 class="mt-5 font-bold text-stone-800">{{ $categoryQuestion }}</h3>
            <p class="text-stone-600">{{ $categoryAnswer }}</p>
            <h3 class="mt-5 font-bold text-stone-800">How can I check the current price?</h3>
            <p class="text-stone-600">Open a product to compare its available sizes. The price shown for a size is the price used at checkout.</p>
            <h3 class="mt-5 font-bold text-stone-800">When will my order arrive?</h3>
            <p class="text-stone-600">Enter your address at checkout to see the delivery charge and estimate for your area before placing an order.</p>
        </section>
    </main>
</x-layouts.storefront>
