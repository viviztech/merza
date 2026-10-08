<x-layouts.storefront title="Wholesale Enquiries" description="Ask Merza about bulk availability and quotes for products currently listed in our catalog.">
    <main class="max-w-5xl mx-auto px-4 py-12">
        <header class="max-w-3xl">
            <p class="text-sm font-bold uppercase tracking-widest text-amber-700">For businesses</p>
            <h1 class="mt-2 text-4xl font-extrabold text-emerald-950">Wholesale & Bulk Enquiries</h1>
            <p class="mt-5 text-stone-600 leading-relaxed">Tell us which products and quantities you need. We will confirm availability, pricing, packing and delivery terms for your order before you commit.</p>
            <a href="https://wa.me/918667696278?text=Hi%2C+I%27m+interested+in+wholesale+pricing+from+Merza." target="_blank" rel="noopener noreferrer" class="mt-6 inline-block rounded-xl bg-emerald-700 px-6 py-3 font-bold text-white hover:bg-emerald-800">Ask for a quote on WhatsApp</a>
        </header>

        <section class="mt-14" aria-labelledby="wholesale-products">
            <h2 id="wholesale-products" class="text-2xl font-extrabold text-emerald-950">Products in our current catalog</h2>
            <p class="mt-2 text-stone-600">Bulk availability and minimum quantities are confirmed individually.</p>
            <div class="mt-6 grid sm:grid-cols-2 md:grid-cols-3 gap-3">
                @forelse($products as $product)
                    <a href="{{ route('products.show', $product->slug) }}" class="rounded-xl border border-emerald-100 bg-white p-4 font-semibold text-emerald-800 hover:shadow-sm">{{ $product->name }}</a>
                @empty
                    <p class="text-stone-600">Please contact us for the current product list.</p>
                @endforelse
            </div>
        </section>

        <section class="mt-14 border-t border-emerald-100 pt-8 max-w-3xl">
            <h2 class="text-2xl font-extrabold text-emerald-950">How to enquire</h2>
            <ol class="mt-4 list-decimal pl-5 space-y-2 text-stone-700">
                <li>Send the product names, quantities and your delivery location.</li>
                <li>We will reply with what is available and a quote.</li>
                <li>Review the final price, delivery timing and payment terms before placing your order.</li>
            </ol>
        </section>
    </main>
</x-layouts.storefront>
