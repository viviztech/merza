<x-layouts.storefront title="Fresh Premium Tropical Fruits">

    {{-- Hero --}}
    <section class="relative bg-gradient-to-br from-green-900 via-green-800 to-green-700 text-white overflow-hidden">
        <div class="absolute inset-0 opacity-10 bg-[url('/images/fruit-pattern.png')] bg-repeat"></div>
        <div class="relative max-w-7xl mx-auto px-4 py-16 md:py-24 flex flex-col md:flex-row items-center gap-8">
            <div class="flex-1 text-center md:text-left">
                <span class="inline-block bg-green-600 text-green-100 text-xs font-semibold px-3 py-1 rounded-full mb-4 uppercase tracking-wider">Fresh from the Farm</span>
                <h1 class="text-3xl md:text-5xl font-bold leading-tight mb-4">
                    Premium Tropical<br>
                    <span class="text-yellow-300">Fruits, Delivered.</span>
                </h1>
                <p class="text-green-100 text-base md:text-lg mb-6 max-w-md mx-auto md:mx-0">
                    Mangoes, Jackfruit, Banana Red, Freeze Dried & Pulp — sourced fresh and delivered straight to your door.
                </p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center md:justify-start">
                    <a href="{{ route('products.index') }}"
                       class="inline-flex items-center justify-center gap-2 bg-yellow-400 hover:bg-yellow-300 text-green-900 font-bold px-6 py-3 rounded-xl transition-all text-base">
                        Shop Now
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                    <a href="https://wa.me/60123456789?text=Hi%2C+I+want+to+order+from+Merza!"
                       target="_blank"
                       class="inline-flex items-center justify-center gap-2 bg-white/10 hover:bg-white/20 border border-white/30 text-white font-semibold px-6 py-3 rounded-xl transition-all text-base">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Order on WhatsApp
                    </a>
                </div>
            </div>
            <div class="flex-1 flex justify-center">
                <div class="w-64 h-64 md:w-80 md:h-80 bg-white/10 rounded-full flex items-center justify-center text-8xl">
                    🥭
                </div>
            </div>
        </div>
    </section>

    {{-- Product categories --}}
    <section class="max-w-7xl mx-auto px-4 py-10">
        <h2 class="text-2xl font-bold text-gray-900 mb-1">Our Products</h2>
        <p class="text-gray-500 text-sm mb-6">Premium quality, farm-fresh tropical fruits</p>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            @foreach([
                ['emoji' => '🥭', 'name' => 'Premium Mangoes',     'sub' => 'Alphonso & Harum Manis', 'color' => 'from-yellow-50 to-orange-50', 'border' => 'border-orange-100'],
                ['emoji' => '🍌', 'name' => 'Banana Red',          'sub' => 'Sweet & Nutritious',      'color' => 'from-red-50 to-pink-50',     'border' => 'border-red-100'],
                ['emoji' => '🍈', 'name' => 'Vietnam Gold Jackfruit','sub' => 'Premium Variety',       'color' => 'from-green-50 to-lime-50',   'border' => 'border-green-100'],
                ['emoji' => '🍋', 'name' => 'Freeze Dried',         'sub' => 'Long Shelf Life',        'color' => 'from-yellow-50 to-amber-50', 'border' => 'border-yellow-100'],
                ['emoji' => '🧃', 'name' => 'Pulp',                 'sub' => 'Mango & Jackfruit',      'color' => 'from-orange-50 to-yellow-50','border' => 'border-orange-100'],
            ] as $product)
                <a href="{{ route('products.index') }}"
                   class="group bg-gradient-to-br {{ $product['color'] }} border {{ $product['border'] }} rounded-2xl p-5 text-center hover:shadow-md hover:-translate-y-1 transition-all duration-200">
                    <div class="text-4xl mb-3">{{ $product['emoji'] }}</div>
                    <h3 class="font-semibold text-sm text-gray-800 leading-tight">{{ $product['name'] }}</h3>
                    <p class="text-xs text-gray-500 mt-1">{{ $product['sub'] }}</p>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Trust signals --}}
    <section class="bg-green-800 text-white py-8">
        <div class="max-w-7xl mx-auto px-4 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
            @foreach([
                ['icon' => '🚚', 'title' => 'Fast Delivery',      'sub' => 'Same-day available'],
                ['icon' => '✅', 'title' => 'Farm Fresh',          'sub' => 'Direct from source'],
                ['icon' => '💬', 'title' => 'WhatsApp Support',   'sub' => '9am – 6pm daily'],
                ['icon' => '📦', 'title' => 'Bulk Orders',        'sub' => 'B2B wholesale available'],
            ] as $trust)
                <div>
                    <div class="text-3xl mb-2">{{ $trust['icon'] }}</div>
                    <div class="font-semibold text-sm">{{ $trust['title'] }}</div>
                    <div class="text-green-300 text-xs mt-1">{{ $trust['sub'] }}</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CTA Banner --}}
    <section class="max-w-7xl mx-auto px-4 py-10">
        <div class="bg-gradient-to-r from-green-700 to-green-600 rounded-2xl p-6 md:p-10 text-white flex flex-col md:flex-row items-center gap-6">
            <div class="flex-1">
                <h2 class="text-xl md:text-2xl font-bold mb-2">Order via WhatsApp</h2>
                <p class="text-green-100 text-sm">Message us directly for bulk orders, custom quantities, or if you have any questions. We respond within minutes.</p>
            </div>
            <a href="https://wa.me/60123456789?text=Hi%2C+I+want+to+place+an+order+from+Merza!"
               target="_blank"
               class="flex-shrink-0 inline-flex items-center gap-2 bg-white text-green-800 font-bold px-6 py-3 rounded-xl hover:bg-green-50 transition-colors text-sm">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                Chat on WhatsApp
            </a>
        </div>
    </section>

</x-layouts.storefront>
