<x-layouts.storefront title="Frequently Asked Questions" description="Answers to common questions about delivery, payment, freshness, and order tracking at Merza.">

    <section class="max-w-3xl mx-auto px-4 py-16">

        <div class="text-center mb-10">
            <span class="text-xs font-bold text-brand-green-dark uppercase tracking-widest">Got Questions?</span>
            <h1 class="text-3xl font-extrabold text-stone-900 mt-1">Frequently Asked Questions</h1>
        </div>

        @include('storefront.partials.faq-list')

        <div class="mt-10 bg-amber-50 border border-amber-100 rounded-2xl p-5 flex items-center justify-between flex-wrap gap-3">
            <div>
                <p class="text-sm font-bold text-stone-800">Still have a question?</p>
                <p class="text-xs text-stone-500 mt-0.5">We're available on WhatsApp</p>
            </div>
            <a href="https://wa.me/919360064278" target="_blank"
               class="flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white text-sm font-bold px-4 py-2.5 rounded-xl transition-all">
                Chat with us
            </a>
        </div>
    </section>
</x-layouts.storefront>
