<x-layouts.storefront title="About Us" description="Merza Bodinayakanur — Mukkani fruits and farm-fresh products. Imam Pasand mangoes, Red bananas, Vietnam Early Gold jackfruit, grown with sustainable care.">

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-emerald-900 via-emerald-800 to-amber-800 text-white py-20 px-4">
        <div class="absolute inset-0 opacity-10" style="background-image: url('/images/logo.png'); background-size: 400px; background-repeat: repeat; background-position: center;"></div>
        <div class="relative max-w-3xl mx-auto text-center">
            <span class="inline-block bg-amber-400/20 border border-amber-400/40 text-amber-300 text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-widest mb-5">
                Bodinayakanur, Tamil Nadu
            </span>
            <h1 class="text-4xl md:text-5xl font-extrabold mb-5 leading-tight">
                Mukkani — Three Fruits,<br>
                <span class="text-amber-400">One Promise.</span>
            </h1>
            <p class="text-emerald-100 text-lg leading-relaxed max-w-2xl mx-auto">
                Fresh, farm-grown fruits and healthy treats that your body will love. Discover the natural goodness of our fields — where every fruit tells a story of care, health, and quality.
            </p>
        </div>
    </section>

    {{-- Our Story --}}
    <section class="max-w-5xl mx-auto px-4 py-16">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            <div>
                <span class="inline-block bg-amber-100 text-amber-700 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider mb-3">Our Story</span>
                <h2 class="text-3xl font-extrabold text-stone-900 mb-5 leading-tight">
                    From our fields to your table
                </h2>
                <p class="text-stone-600 leading-relaxed mb-4">
                    We grow <strong class="text-stone-800">Imam Pasand mangoes</strong>, <strong class="text-stone-800">Red bananas</strong>, and <strong class="text-stone-800">Vietnam Early Gold jackfruit</strong> using sustainable agricultural methods that preserve nature and enhance flavour.
                </p>
                <p class="text-stone-600 leading-relaxed mb-4">
                    From our farms to your table, we bring you not just fruits — but a healthy experience. Our products are <strong class="text-stone-800">100% real</strong>, free from artificial flavours and colours.
                </p>
                <p class="text-stone-600 leading-relaxed">
                    Make the smart choice — go natural, go healthy. Try our farm-fresh fruits and homemade-style products today.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                @foreach([
                    ['🌿', 'Sustainable Farming', 'Agricultural methods that preserve nature and enhance flavour naturally.'],
                    ['✅', '100% Natural', 'Free from artificial flavours, colours, and preservatives.'],
                    ['🏡', 'Farm Direct', 'Grown in our own fields — no middlemen, no compromise.'],
                    ['❤️', 'Family Goodness', 'Perfect for families, kids, and anyone who values purity with taste.'],
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

    {{-- Mukkani — Three Fruits --}}
    <section class="bg-gradient-to-br from-amber-50 to-emerald-50 py-16 px-4">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-10">
                <span class="inline-block bg-emerald-100 text-emerald-700 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider mb-3">Mukkani Fruits</span>
                <h2 class="text-2xl font-extrabold text-stone-900 mb-2">The Three Sacred Fruits</h2>
                <p class="text-stone-500 text-sm max-w-xl mx-auto">In Tamil tradition, Mukkani (முக்கனி) means the three prized fruits — Mango, Banana, and Jackfruit. We grow all three on our farm.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-6 mb-10">
                @foreach([
                    ['🥭', 'Imam Pasand Mango', 'The king of mangoes — rich, creamy, and intensely sweet. Our Imam Pasand variety is grown with care for the finest flavour.', 'bg-yellow-50 border-yellow-200'],
                    ['🍌', 'Red Banana', 'Naturally sweeter and creamier than the common banana, packed with nutrients and antioxidants. A wholesome treat for the whole family.', 'bg-pink-50 border-pink-200'],
                    ['🍈', 'Vietnam Early Gold Jackfruit', 'Thin-seeded with golden, aromatic flesh. Our Vietnam Early Gold variety delivers tropical sweetness in every bite.', 'bg-emerald-50 border-emerald-200'],
                ] as [$icon, $name, $desc, $classes])
                    <div class="rounded-2xl p-6 border {{ $classes }} text-center">
                        <div class="text-5xl mb-4">{{ $icon }}</div>
                        <h3 class="font-extrabold text-stone-900 mb-3">{{ $name }}</h3>
                        <p class="text-stone-500 text-sm leading-relaxed">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Processed Products --}}
            <div class="text-center mb-8">
                <h3 class="text-xl font-extrabold text-stone-900 mb-2">Our Farm Products</h3>
                <p class="text-stone-500 text-sm">Crafted from freshly harvested fruits — homemade quality in every jar and bottle.</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                @foreach([
                    ['🍊', 'Orange Squash', 'Tangy, refreshing, made from fresh oranges with zero artificial colour.'],
                    ['🍦', 'Banana Ice Cream', 'Creamy, smooth, and naturally sweet — made from ripe red bananas.'],
                    ['🍯', 'Mango Jam', 'Tropical sweetness captured in a jar, perfect on toast or with snacks.'],
                ] as [$icon, $name, $desc])
                    <div class="bg-white rounded-2xl p-5 text-center shadow-sm border border-amber-100">
                        <div class="text-3xl mb-2">{{ $icon }}</div>
                        <div class="font-bold text-stone-900 text-sm mb-1">{{ $name }}</div>
                        <div class="text-stone-400 text-xs leading-relaxed">{{ $desc }}</div>
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
                ['🌱', 'Health First', 'Everything we grow and make is designed to keep your health and taste buds in mind. Pure ingredients, no shortcuts.'],
                ['🤝', 'Honest Farming', 'Sustainable agricultural methods that preserve nature and enhance the natural flavour of every fruit we grow.'],
                ['❤️', 'Community Roots', 'We\'re proud to be from Bodinayakanur — growing local, selling local, and building a healthier community.'],
            ] as [$icon, $title, $desc])
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-stone-100">
                    <div class="text-3xl mb-3">{{ $icon }}</div>
                    <h3 class="font-bold text-stone-900 mb-2">{{ $title }}</h3>
                    <p class="text-stone-500 text-sm leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Find Our Store + Map --}}
    <section class="bg-emerald-900 text-white py-16 px-4">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-10">
                <span class="inline-block bg-amber-400/20 border border-amber-400/40 text-amber-300 text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-widest mb-4">Visit Us</span>
                <h2 class="text-2xl font-extrabold mb-2">Find Our Store</h2>
                <p class="text-emerald-300 text-sm">Come visit us for a fresh and flavorful experience like no other!</p>
            </div>

            <div class="grid md:grid-cols-2 gap-8 items-start">
                {{-- Address + Contact --}}
                <div class="space-y-6">
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                        <h3 class="font-bold text-amber-300 text-sm uppercase tracking-wider mb-4">Store Location</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-start gap-3">
                                <span class="text-lg mt-0.5">📍</span>
                                <div>
                                    <p class="font-bold text-white">Merza Natural Squash</p>
                                    <p class="text-emerald-200">HP Petrol Bunk,</p>
                                    <p class="text-emerald-200">Pankajam School Opposite,</p>
                                    <p class="text-emerald-200">Thevaram Road,</p>
                                    <p class="text-emerald-200">Bodinayakanur — 625513</p>
                                    <p class="text-emerald-200">Tamil Nadu, India</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-lg">📞</span>
                                <a href="tel:+918667696278" class="text-emerald-200 hover:text-white transition-colors">+91 86676 96278</a>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-lg">✉️</span>
                                <a href="mailto:merzabodinayakanur@gmail.com" class="text-emerald-200 hover:text-white transition-colors">merzabodinayakanur@gmail.com</a>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-lg">🕐</span>
                                <span class="text-emerald-200">Mon – Sat: 9:00 AM – 6:00 PM</span>
                            </div>
                        </div>
                        <div class="mt-5 flex flex-col sm:flex-row gap-3">
                            <a href="https://maps.app.goo.gl/opSvcoTzZrS1KvVr9" target="_blank"
                               class="inline-flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-400 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Get Directions
                            </a>
                            <a href="https://wa.me/918667696278?text=Hi%2C+I+want+to+visit+your+store+or+place+an+order!" target="_blank"
                               class="inline-flex items-center justify-center gap-2 bg-green-500 hover:bg-green-400 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition-all">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                WhatsApp Order
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Google Map --}}
                <div class="rounded-2xl overflow-hidden border border-white/20 shadow-xl" style="height: 380px;">
                    <iframe
                        src="https://maps.google.com/maps?q=HP+Petrol+Bunk+Pankajam+School+Thevaram+Road+Bodinayakanur+Tamil+Nadu&output=embed&z=16&hl=en"
                        width="100%"
                        height="100%"
                        style="border:0; display:block;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Merza Store Location — Thevaram Road, Bodinayakanur">
                    </iframe>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="bg-amber-500 text-white py-12 px-4">
        <div class="max-w-xl mx-auto text-center">
            <h2 class="text-2xl font-extrabold mb-3">Ready to taste the difference?</h2>
            <p class="text-amber-100 mb-6 text-sm">Browse our current selection or contact us directly — we reply fast!</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('products.index') }}"
                   class="bg-white text-amber-700 hover:bg-amber-50 font-bold px-6 py-3 rounded-xl text-sm transition-all">
                    Shop Now
                </a>
                <a href="https://wa.me/918667696278?text=Hi+Merza%2C+I+want+to+place+an+order!"
                   target="_blank"
                   class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-6 py-3 rounded-xl text-sm transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Chat with Us
                </a>
            </div>
        </div>
    </section>

</x-layouts.storefront>
