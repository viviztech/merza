<x-layouts.storefront title="Blog & Recipes" description="Tropical fruit recipes, tips, and inspiration from Merza. Mangoes, jackfruit, banana recipes and more.">

    {{-- Hero --}}
    <section class="bg-gradient-to-br from-amber-600 to-orange-500 text-white py-16 px-4">
        <div class="max-w-3xl mx-auto text-center">
            <span class="text-5xl mb-4 block">🍴</span>
            <h1 class="text-4xl font-extrabold mb-3">Recipes & Inspiration</h1>
            <p class="text-amber-100 text-lg">Delicious ways to enjoy your tropical fruits — from quick snacks to full meals.</p>
        </div>
    </section>

    {{-- Coming Soon Banner --}}
    <div class="bg-amber-50 border-b border-amber-100 py-3 px-4 text-center text-sm text-amber-700">
        🚧 Full blog coming soon — follow us on WhatsApp for weekly recipe tips!
        <a href="https://wa.me/60123456789?text=Hi%2C+I%27d+like+to+get+recipe+tips+from+Merza!" target="_blank" class="underline font-semibold ml-1">Join now →</a>
    </div>

    {{-- Recipe Cards --}}
    <section class="max-w-5xl mx-auto px-4 py-16">
        <h2 class="text-2xl font-extrabold text-stone-900 mb-8">Try These Today</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">

            @foreach([
                [
                    'emoji' => '🥭',
                    'tag' => 'Dessert',
                    'tag_color' => 'amber',
                    'title' => 'Mango Sticky Rice',
                    'time' => '30 min',
                    'difficulty' => 'Easy',
                    'desc' => 'Classic Thai-style sticky rice with fresh Harumanis mango slices and coconut cream drizzle.',
                    'ingredients' => ['2 cups glutinous rice', '1 ripe Harumanis mango', '1 can coconut milk', '3 tbsp sugar', 'Pinch of salt'],
                    'steps' => 'Cook soaked glutinous rice in coconut milk with sugar and salt until fluffy. Slice mango thinly. Serve rice topped with mango and a drizzle of thickened coconut cream.',
                ],
                [
                    'emoji' => '🍈',
                    'tag' => 'Main Dish',
                    'tag_color' => 'emerald',
                    'title' => 'Young Jackfruit Curry',
                    'time' => '45 min',
                    'difficulty' => 'Medium',
                    'desc' => 'Savory jackfruit curry that works as a hearty meat-free main. Rich, aromatic and satisfying.',
                    'ingredients' => ['500g young jackfruit (canned ok)', '2 tbsp curry powder', '1 can coconut milk', 'Onion, garlic, ginger', 'Salt and palm sugar to taste'],
                    'steps' => 'Sauté aromatics, add curry powder and jackfruit, pour in coconut milk. Simmer 30 min until jackfruit is tender and pulls apart. Serve with rice.',
                ],
                [
                    'emoji' => '🥤',
                    'tag' => 'Drink',
                    'tag_color' => 'orange',
                    'title' => 'Tropical Mango Lassi',
                    'time' => '5 min',
                    'difficulty' => 'Easy',
                    'desc' => 'A creamy, refreshing Indian-style mango drink. Perfect for hot Malaysian afternoons.',
                    'ingredients' => ['2 ripe mangoes (or 200ml pulp)', '1 cup plain yogurt', '½ cup cold milk', '2 tbsp sugar', 'Cardamom pinch, ice'],
                    'steps' => 'Blend all ingredients until smooth. Adjust sweetness to taste. Serve immediately over ice. Top with a pinch of cardamom.',
                ],
                [
                    'emoji' => '🍌',
                    'tag' => 'Breakfast',
                    'tag_color' => 'yellow',
                    'title' => 'Pisang Berangan Smoothie Bowl',
                    'time' => '10 min',
                    'difficulty' => 'Easy',
                    'desc' => 'Thick, creamy banana base loaded with tropical toppings — filling and nutritious.',
                    'ingredients' => ['3 frozen Pisang Berangan', '¼ cup coconut milk', 'Toppings: granola, honey, chia seeds, sliced fruit'],
                    'steps' => 'Blend frozen bananas with coconut milk until thick and creamy (not liquid). Pour into a bowl. Top with granola, honey, and your choice of fruits.',
                ],
                [
                    'emoji' => '🍋',
                    'tag' => 'Snack',
                    'tag_color' => 'lime',
                    'title' => 'Freeze Dried Fruit Trail Mix',
                    'time' => '2 min',
                    'difficulty' => 'Easy',
                    'desc' => 'A no-prep tropical snack mix. Great for school boxes, offices and travel.',
                    'ingredients' => ['Merza freeze dried mango slices', 'Roasted cashews or almonds', 'Desiccated coconut', 'Dark chocolate chips (optional)', 'Pumpkin seeds'],
                    'steps' => 'Combine all ingredients in a jar or zip bag. Shake to mix. Keeps at room temperature for up to 2 weeks. The freeze dried fruit adds intense flavour without moisture.',
                ],
                [
                    'emoji' => '🧃',
                    'tag' => 'Drink',
                    'tag_color' => 'orange',
                    'title' => 'Mango Puree Mocktail',
                    'time' => '5 min',
                    'difficulty' => 'Easy',
                    'desc' => 'Refreshing, vibrant and impressive — ready in minutes using Merza mango puree.',
                    'ingredients' => ['100ml Merza mango puree', 'Sparkling water or soda', 'Fresh lime juice', 'Mint leaves', 'Ice cubes'],
                    'steps' => 'Pour mango puree over ice. Squeeze half a lime. Top up with sparkling water. Garnish with mint and serve immediately. Add chilli salt to the rim for a twist.',
                ],
            ] as $recipe)
                <div class="bg-white rounded-2xl shadow-sm border border-stone-100 overflow-hidden flex flex-col">
                    <div class="bg-gradient-to-br from-amber-50 to-orange-50 p-8 text-center text-5xl">
                        {{ $recipe['emoji'] }}
                    </div>
                    <div class="p-5 flex flex-col flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">{{ $recipe['tag'] }}</span>
                            <span class="text-xs text-stone-400">⏱ {{ $recipe['time'] }}</span>
                            <span class="text-xs text-stone-400">· {{ $recipe['difficulty'] }}</span>
                        </div>
                        <h3 class="font-extrabold text-stone-900 text-base mb-1">{{ $recipe['title'] }}</h3>
                        <p class="text-stone-500 text-xs leading-relaxed mb-4">{{ $recipe['desc'] }}</p>

                        <div class="mt-auto">
                            <details class="group">
                                <summary class="text-xs font-semibold text-amber-700 cursor-pointer hover:text-amber-800 list-none flex items-center gap-1">
                                    <span>View Recipe</span>
                                    <svg class="w-3 h-3 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </summary>
                                <div class="mt-3 space-y-3">
                                    <div>
                                        <p class="text-xs font-semibold text-stone-700 mb-1">Ingredients</p>
                                        <ul class="text-xs text-stone-500 space-y-0.5">
                                            @foreach($recipe['ingredients'] as $ing)
                                                <li class="flex items-start gap-1"><span class="text-amber-400 mt-0.5">•</span> {{ $ing }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold text-stone-700 mb-1">Method</p>
                                        <p class="text-xs text-stone-500 leading-relaxed">{{ $recipe['steps'] }}</p>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CTA --}}
    <section class="bg-emerald-900 text-white py-14 px-4">
        <div class="max-w-xl mx-auto text-center">
            <h2 class="text-2xl font-extrabold mb-3">Get the freshest ingredients</h2>
            <p class="text-emerald-300 mb-6 text-sm">Order premium tropical fruits to make these recipes at home.</p>
            <a href="{{ route('products.index') }}"
               class="inline-block bg-amber-500 hover:bg-amber-400 text-white font-bold px-8 py-3 rounded-xl text-sm transition-all">
                Shop Fresh Fruits
            </a>
        </div>
    </section>

</x-layouts.storefront>
