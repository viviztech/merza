<x-layouts.storefront title="Careers" description="Join the Merza team. We're growing fast and looking for passionate people to help us deliver premium tropical fruits across India.">

    {{-- Hero --}}
    <section class="bg-gradient-to-br from-emerald-900 to-emerald-700 text-white py-20 px-4">
        <div class="max-w-3xl mx-auto text-center">
            <span class="text-5xl mb-4 block">🌱</span>
            <h1 class="text-4xl md:text-5xl font-extrabold mb-4 leading-tight">
                Grow With Us
            </h1>
            <p class="text-emerald-200 text-lg leading-relaxed max-w-2xl mx-auto">
                We're a small team with big ambitions. If you love fresh food, fast growth, and meaningful work — you'll fit right in.
            </p>
        </div>
    </section>

    {{-- Culture --}}
    <section class="max-w-5xl mx-auto px-4 py-16">
        <div class="grid md:grid-cols-3 gap-6 mb-12">
            @foreach([
                ['🚀', 'Move Fast', 'We\'re early stage and growing. Your work makes an immediate impact — no bureaucracy, no waiting around.'],
                ['🤝', 'Team First', 'Small team, big trust. Everyone owns their role and supports each other across all areas of the business.'],
                ['🌴', 'Love What You Sell', 'We genuinely care about our products. If you love food, freshness, and quality — this is the place for you.'],
            ] as [$icon, $title, $desc])
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-stone-100">
                    <div class="text-3xl mb-3">{{ $icon }}</div>
                    <h3 class="font-bold text-stone-900 mb-2">{{ $title }}</h3>
                    <p class="text-stone-500 text-sm leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Open Roles --}}
    <section class="bg-amber-50 py-16 px-4">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-2xl font-extrabold text-stone-900 mb-2 text-center">Open Positions</h2>
            <p class="text-stone-500 text-sm text-center mb-10">All roles are based in Bodinayakanur unless noted. Apply via WhatsApp.</p>

            <div class="space-y-4">
                @foreach([
                    [
                        'title' => 'Delivery Rider',
                        'type' => 'Full-time / Part-time',
                        'icon' => '🚚',
                        'desc' => 'Deliver fresh fruit orders across Bodinayakanur and surrounding areas. Own two-wheeler required. Fuel allowance + commission per delivery.',
                        'req' => ['Valid Indian driving licence', 'Own two-wheeler in good condition', 'Smartphone for delivery app', 'Reliable and punctual'],
                    ],
                    [
                        'title' => 'Sales & Customer Service',
                        'type' => 'Full-time',
                        'icon' => '💬',
                        'desc' => 'Handle WhatsApp orders, follow up on leads, and ensure customers are delighted. No cold calling — our customers come to us.',
                        'req' => ['Good written Tamil & English', 'Friendly and responsive communication', 'Basic smartphone literacy', 'Sales experience a bonus but not required'],
                    ],
                    [
                        'title' => 'Social Media & Content',
                        'type' => 'Part-time / Freelance',
                        'icon' => '📱',
                        'desc' => 'Create content for Instagram and TikTok showcasing our fruits, recipes, and behind-the-scenes. Eye for food photography is a must.',
                        'req' => ['Active on Instagram/TikTok', 'Basic photo/video editing skills', 'Creative and self-directed', 'Passion for food content'],
                    ],
                    [
                        'title' => 'Packing & Warehouse',
                        'type' => 'Full-time',
                        'icon' => '📦',
                        'desc' => 'Pack and quality-check fruit orders at our fulfilment centre. Attention to detail and ability to work efficiently in a team.',
                        'req' => ['Physically fit, able to lift 15kg', 'Detail-oriented and careful with produce', 'Available for early morning shifts', 'Experience in food handling a plus'],
                    ],
                ] as $role)
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-amber-100">
                        <div class="flex items-start justify-between gap-4 mb-3">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">{{ $role['icon'] }}</span>
                                <div>
                                    <h3 class="font-bold text-stone-900">{{ $role['title'] }}</h3>
                                    <span class="text-xs text-emerald-700 font-semibold bg-emerald-50 px-2 py-0.5 rounded-full">{{ $role['type'] }}</span>
                                </div>
                            </div>
                            <a href="https://wa.me/919360064278?text=Hi%2C+I%27d+like+to+apply+for+the+{{ urlencode($role['title']) }}+position+at+Merza."
                               target="_blank"
                               class="flex-shrink-0 bg-green-500 hover:bg-green-400 text-white font-bold px-4 py-2 rounded-xl text-xs transition-all">
                                Apply
                            </a>
                        </div>
                        <p class="text-stone-500 text-sm mb-3">{{ $role['desc'] }}</p>
                        <ul class="text-xs text-stone-500 space-y-1">
                            @foreach($role['req'] as $req)
                                <li class="flex items-start gap-1.5"><span class="text-amber-400 mt-0.5">✓</span> {{ $req }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>

            <div class="text-center mt-8 p-6 bg-white rounded-2xl border border-amber-100">
                <p class="text-stone-600 text-sm mb-3">Don't see a role that fits? We're always open to talented people.</p>
                <a href="https://wa.me/919360064278?text=Hi%2C+I%27d+like+to+introduce+myself+and+explore+opportunities+at+Merza."
                   target="_blank"
                   class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 hover:text-emerald-800">
                    Send us your introduction →
                </a>
            </div>
        </div>
    </section>

</x-layouts.storefront>
