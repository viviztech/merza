<x-layouts.storefront title="About Us" description="Learn about Merza — our story, mission, and commitment to delivering premium tropical fruits fresh from the farm.">

    {{-- Hero --}}
    <section class="bg-gradient-to-br from-emerald-900 via-emerald-800 to-emerald-700 text-white py-20 px-4">
        <div class="max-w-3xl mx-auto text-center">
            <span class="text-5xl mb-4 block">🥭</span>
            <h1 class="text-4xl md:text-5xl font-extrabold mb-4 leading-tight">
                Our Story
            </h1>
            <p class="text-emerald-200 text-lg leading-relaxed max-w-2xl mx-auto">
                Born from a love of tropical fruit and a frustration with inconsistent quality, Merza was built to bring you the very best — straight from the farm to your door.
            </p>
        </div>
    </section>

    {{-- Mission --}}
    <section class="max-w-5xl mx-auto px-4 py-16">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            <div>
                <span class="inline-block bg-amber-100 text-amber-700 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider mb-3">Our Mission</span>
                <h2 class="text-3xl font-extrabold text-stone-900 mb-4 leading-tight">
                    Premium tropical fruits, delivered with care
                </h2>
                <p class="text-stone-600 leading-relaxed mb-4">
                    We partner directly with trusted farms across Malaysia, Thailand, and Vietnam to source only the finest seasonal fruits — no middlemen, no compromise on quality.
                </p>
                <p class="text-stone-600 leading-relaxed">
                    Every batch is hand-picked, quality-checked, and packed fresh before it reaches your door. We believe everyone deserves fruit that actually tastes like fruit should.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                @foreach([
                    ['🌿', 'Farm Direct', 'Sourced straight from trusted farms with no unnecessary middlemen.'],
                    ['✅', 'Quality Checked', 'Every batch inspected for freshness, ripeness, and appearance.'],
                    ['📦', 'Packed Fresh', 'Orders packed to order and dispatched same or next day.'],
                    ['🚚', 'Fast Delivery', 'Nationwide delivery across Malaysia within 1–3 days.'],
                ] as [$icon, $title, $desc])
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-amber-100">
                        <div class="text-2xl mb-2">{{ $icon }}</div>
                        <div class="font-bold text-stone-900 text-sm mb-1">{{ $title }}</div>
                        <div class="text-stone-500 text-xs leading-relaxed">{{ $desc }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Products we carry --}}
    <section class="bg-amber-50 py-16 px-4">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-10">
                <h2 class="text-2xl font-extrabold text-stone-900 mb-2">What We Carry</h2>
                <p class="text-stone-500 text-sm">Seasonal availability may vary — check our products page for what's fresh today.</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                @foreach([
                    ['🥭', 'Premium Mangoes', 'Harumanis, Chokanan & imported varieties'],
                    ['🍌', 'Banana Red', 'Pisang Berangan — sweet and creamy'],
                    ['🍈', 'Vietnam Gold Jackfruit', 'Thin-seeded, golden flesh, sweet aroma'],
                    ['🍋', 'Freeze Dried', 'Crispy, concentrated flavour, long shelf life'],
                    ['🧃', 'Pulp & Puree', 'Ready for smoothies, desserts & cooking'],
                ] as [$icon, $name, $desc])
                    <div class="bg-white rounded-2xl p-5 text-center shadow-sm border border-amber-100">
                        <div class="text-3xl mb-2">{{ $icon }}</div>
                        <div class="font-bold text-stone-900 text-sm mb-1">{{ $name }}</div>
                        <div class="text-stone-400 text-xs">{{ $desc }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Values --}}
    <section class="max-w-5xl mx-auto px-4 py-16">
        <div class="text-center mb-10">
            <h2 class="text-2xl font-extrabold text-stone-900 mb-2">What We Stand For</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-6">
            @foreach([
                ['🌱', 'Freshness First', 'We only sell what\'s in season and at peak ripeness. If it doesn\'t meet our standard, it doesn\'t go out.'],
                ['🤝', 'Honest Business', 'No hidden charges. What you see is what you pay. We communicate clearly about delivery timelines and product availability.'],
                ['❤️', 'Customer First', 'Every order matters. If something isn\'t right, we\'ll make it right. Reach us on WhatsApp anytime.'],
            ] as [$icon, $title, $desc])
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-stone-100">
                    <div class="text-3xl mb-3">{{ $icon }}</div>
                    <h3 class="font-bold text-stone-900 mb-2">{{ $title }}</h3>
                    <p class="text-stone-500 text-sm leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CTA --}}
    <section class="bg-emerald-900 text-white py-14 px-4">
        <div class="max-w-xl mx-auto text-center">
            <h2 class="text-2xl font-extrabold mb-3">Ready to taste the difference?</h2>
            <p class="text-emerald-300 mb-6 text-sm">Browse our current selection or chat with us directly on WhatsApp.</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('products.index') }}"
                   class="bg-amber-500 hover:bg-amber-400 text-white font-bold px-6 py-3 rounded-xl text-sm transition-all">
                    Shop Now
                </a>
                <a href="https://wa.me/60123456789?text=Hi%2C+I%27d+like+to+know+more+about+Merza!"
                   target="_blank"
                   class="bg-green-500 hover:bg-green-400 text-white font-bold px-6 py-3 rounded-xl text-sm transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Chat with Us
                </a>
            </div>
        </div>
    </section>

</x-layouts.storefront>
