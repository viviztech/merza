<x-layouts.storefront :title="$recipe['title'] . ' — Recipe'">
    <main class="max-w-4xl mx-auto px-4 py-10">
        <nav aria-label="Breadcrumb" class="mb-6 text-sm text-stone-500">
            <a href="{{ route('home') }}" class="hover:text-emerald-700">Home</a> /
            <a href="{{ route('blog') }}" class="hover:text-emerald-700">Recipes</a> /
            <span class="text-stone-800">{{ $recipe['title'] }}</span>
        </nav>
        <article>
            <p class="text-sm font-bold uppercase tracking-widest text-amber-700">{{ $recipe['time'] }}</p>
            <h1 class="mt-2 text-3xl md:text-4xl font-extrabold text-emerald-950">{{ $recipe['title'] }}</h1>
            <p class="mt-4 text-lg text-stone-600 leading-relaxed">{{ $recipe['summary'] }}</p>
            @php $image = $recipe['product']->getFirstMediaUrl('thumbnail', 'card') ?: $recipe['product']->getFirstMediaUrl('images', 'card'); @endphp
            <img src="{{ $image ?: asset('images/placeholder-product.png') }}" alt="{{ $recipe['product']->name }}" class="mt-8 w-full max-h-96 object-cover rounded-2xl">

            <div class="mt-10 grid md:grid-cols-2 gap-10">
                <section>
                    <h2 class="text-xl font-extrabold text-stone-900">Ingredients</h2>
                    <ul class="mt-4 list-disc pl-5 space-y-2 text-stone-700">
                        @foreach($recipe['ingredients'] as $ingredient)<li>{{ $ingredient }}</li>@endforeach
                    </ul>
                </section>
                <section>
                    <h2 class="text-xl font-extrabold text-stone-900">Method</h2>
                    <ol class="mt-4 list-decimal pl-5 space-y-3 text-stone-700">
                        @foreach($recipe['steps'] as $step)<li>{{ $step }}</li>@endforeach
                    </ol>
                </section>
            </div>
            <div class="mt-12 rounded-2xl border border-emerald-200 bg-emerald-50 p-6">
                <p class="font-bold text-emerald-950">Featured ingredient</p>
                <a href="{{ route('products.show', $recipe['product']->slug) }}" class="mt-2 inline-block text-emerald-800 underline">{{ $recipe['product']->name }}</a>
            </div>
        </article>
    </main>
</x-layouts.storefront>
