<x-layouts.storefront title="Recipes & Ideas" description="Practical recipes using products currently available from Merza, including fresh fruit and fruit snacks.">
    <main class="max-w-6xl mx-auto px-4 py-12">
        <header class="max-w-2xl mb-10">
            <p class="text-sm font-bold uppercase tracking-widest text-amber-700">From the Merza kitchen</p>
            <h1 class="mt-2 text-4xl font-extrabold text-emerald-950">Recipes & Ideas</h1>
            <p class="mt-4 text-stone-600">Simple ways to enjoy products in our current catalog.</p>
        </header>
        @if($recipes)
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($recipes as $recipe)
                    @php $image = $recipe['product']->getFirstMediaUrl('thumbnail', 'card') ?: $recipe['product']->getFirstMediaUrl('images', 'card'); @endphp
                    <article class="rounded-2xl overflow-hidden border border-emerald-100 bg-white hover:shadow-lg transition-shadow">
                        <a href="{{ route('blog.recipe', $recipe['slug']) }}" class="block">
                            <img src="{{ $image ?: asset('images/placeholder-product.png') }}" alt="{{ $recipe['product']->name }}" loading="lazy" class="aspect-[4/3] w-full object-cover">
                            <div class="p-5">
                                <p class="text-xs font-bold uppercase tracking-wide text-amber-700">{{ $recipe['time'] }}</p>
                                <h2 class="mt-2 text-xl font-extrabold text-stone-900">{{ $recipe['title'] }}</h2>
                                <p class="mt-2 text-sm text-stone-600 leading-relaxed">{{ $recipe['summary'] }}</p>
                                <span class="mt-4 inline-block text-sm font-bold text-emerald-700">Read recipe →</span>
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>
        @else
            <p class="rounded-2xl bg-emerald-50 p-6 text-stone-700">Recipes will appear here when their ingredients are available in the catalog. <a href="{{ route('products.index') }}" class="font-bold text-emerald-800 underline">Browse current products</a>.</p>
        @endif
    </main>
</x-layouts.storefront>
