<x-layouts.storefront title="B2B Wholesale" description="Wholesale and bulk orders for restaurants, hotels, smoothie bars and retailers. Get competitive pricing on premium tropical fruits.">

    {{-- Hero --}}
    <section class="bg-gradient-to-br from-emerald-900 to-emerald-700 text-white py-20 px-4">
        <div class="max-w-3xl mx-auto text-center">
            <span class="text-5xl mb-4 block">🏢</span>
            <h1 class="text-4xl md:text-5xl font-extrabold mb-4 leading-tight">
                Wholesale & Bulk Orders
            </h1>
            <p class="text-emerald-200 text-lg leading-relaxed max-w-2xl mx-auto">
                Consistent quality. Competitive pricing. Reliable supply. Partner with Merza for your business fruit needs.
            </p>
            <a href="https://wa.me/918667696278?text=Hi%2C+I%27m+interested+in+wholesale+pricing+from+Merza."
               target="_blank"
               class="inline-flex items-center gap-2 mt-8 bg-green-500 hover:bg-green-400 text-white font-bold px-7 py-3 rounded-xl text-sm transition-all shadow-lg">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                Get a Quote on WhatsApp
            </a>
        </div>
    </section>

    {{-- Who we work with --}}
    <section class="max-w-5xl mx-auto px-4 py-16">
        <div class="text-center mb-10">
            <h2 class="text-2xl font-extrabold text-stone-900 mb-2">Who We Supply</h2>
            <p class="text-stone-500 text-sm">We work with businesses of all sizes across India.</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach([
                ['🍽️', 'Restaurants & Cafés', 'Fresh fruit for dishes, desserts and drinks menus.'],
                ['🏨', 'Hotels & Resorts', 'Buffet supply and in-room amenity fruit baskets.'],
                ['🥤', 'Smoothie & Juice Bars', 'Pulp, frozen and fresh supply for daily operations.'],
                ['🛒', 'Retailers & Grocers', 'Branded or unbranded resale supply with consistent stock.'],
            ] as [$icon, $title, $desc])
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-amber-100 text-center">
                    <div class="text-3xl mb-3">{{ $icon }}</div>
                    <div class="font-bold text-stone-900 text-sm mb-2">{{ $title }}</div>
                    <div class="text-stone-400 text-xs leading-relaxed">{{ $desc }}</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Benefits --}}
    <section class="bg-amber-50 py-16 px-4">
        <div class="max-w-5xl mx-auto">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-2xl font-extrabold text-stone-900 mb-6">Why Choose Merza for Wholesale?</h2>
                    <div class="space-y-4">
                        @foreach([
                            ['✅', 'Consistent Quality', 'Same standard every delivery. We reject substandard batches before they reach you.'],
                            ['💰', 'Competitive Pricing', 'Volume-based pricing that improves as your orders grow. Ask us for a tiered quote.'],
                            ['📅', 'Scheduled Deliveries', 'Set weekly or bi-weekly delivery schedules so you never run out.'],
                            ['📞', 'Dedicated Support', 'A direct WhatsApp line to your account manager for every order and issue.'],
                            ['📦', 'Flexible MOQ', 'Minimum order quantities starting from 10kg for most products. We grow with you.'],
                        ] as [$icon, $title, $desc])
                            <div class="flex gap-3">
                                <span class="text-lg mt-0.5">{{ $icon }}</span>
                                <div>
                                    <div class="font-semibold text-stone-900 text-sm">{{ $title }}</div>
                                    <div class="text-stone-500 text-xs leading-relaxed mt-0.5">{{ $desc }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Products available wholesale --}}
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-amber-100">
                    <h3 class="font-bold text-stone-900 mb-4 text-sm uppercase tracking-wider">Products Available</h3>
                    <div class="space-y-3">
                        @foreach([
                            ['🥭', 'Premium Mangoes', 'from 10kg', 'Harumanis, Chokanan, Indian Alphonso'],
                            ['🍌', 'Banana Red', 'from 15kg', 'Pisang Berangan, ready for smoothies'],
                            ['🍈', 'Vietnam Gold Jackfruit', 'from 5kg', 'Seeded & seedless, fresh or frozen'],
                            ['🍋', 'Freeze Dried Range', 'from 2kg', 'Mango, jackfruit, banana slices'],
                            ['🧃', 'Pulp & Puree', 'from 5L', 'Mango, passion fruit, guava puree'],
                        ] as [$icon, $name, $moq, $note])
                            <div class="flex items-center justify-between py-2 border-b border-stone-50 last:border-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">{{ $icon }}</span>
                                    <div>
                                        <div class="font-medium text-stone-900 text-sm">{{ $name }}</div>
                                        <div class="text-stone-400 text-xs">{{ $note }}</div>
                                    </div>
                                </div>
                                <span class="text-xs bg-emerald-50 text-emerald-700 font-semibold px-2 py-1 rounded-lg whitespace-nowrap">{{ $moq }}</span>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-stone-400 mt-4">* MOQ = Minimum Order Quantity. Pricing provided on request.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="max-w-5xl mx-auto px-4 py-16">
        <div class="text-center mb-10">
            <h2 class="text-2xl font-extrabold text-stone-900 mb-2">How to Get Started</h2>
        </div>
        <div class="grid md:grid-cols-4 gap-6">
            @foreach([
                ['1', '💬', 'Chat with Us', 'Message us on WhatsApp with your requirements — what you need and how much.'],
                ['2', '📋', 'Receive Quote', 'We\'ll send a customised price list and delivery schedule within 24 hours.'],
                ['3', '✅', 'Confirm Order', 'Agree on terms and place your first order. Payment via bank transfer.'],
                ['4', '🚚', 'Get Delivered', 'Fresh stock arrives on schedule. We follow up after each delivery.'],
            ] as [$step, $icon, $title, $desc])
                <div class="text-center">
                    <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-700 font-extrabold text-sm flex items-center justify-center mx-auto mb-3">{{ $step }}</div>
                    <div class="text-2xl mb-2">{{ $icon }}</div>
                    <div class="font-bold text-stone-900 text-sm mb-1">{{ $title }}</div>
                    <div class="text-stone-500 text-xs leading-relaxed">{{ $desc }}</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CTA --}}
    <section class="bg-emerald-900 text-white py-14 px-4">
        <div class="max-w-xl mx-auto text-center">
            <h2 class="text-2xl font-extrabold mb-3">Let's talk business</h2>
            <p class="text-emerald-300 mb-6 text-sm">Send us a WhatsApp and we'll get back to you within 2 hours during business hours (Mon–Sat, 9am–6pm).</p>
            <a href="https://wa.me/918667696278?text=Hi%2C+I%27m+interested+in+wholesale+pricing+from+Merza.+My+business+is%3A+"
               target="_blank"
               class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-400 text-white font-bold px-7 py-3 rounded-xl text-sm transition-all">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                Start a Wholesale Enquiry
            </a>
        </div>
    </section>

</x-layouts.storefront>
