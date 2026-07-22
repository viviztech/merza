@php
    $faqItems = [
        ['Which areas do you deliver to?', 'We deliver across Tamil Nadu and to select cities nationwide — see the Delivery Information section on the homepage for zones and rates, or message us on WhatsApp to check your area.'],
        ['What payment methods do you accept?', 'UPI (Google Pay, PhonePe, or any UPI app via QR code) and Cash on Delivery, where available. Card payments are coming soon.'],
        ['Are your fruits naturally ripened?', 'Yes — all our fruits are grown on our own farm in Bodinayakanur and naturally ripened, with no artificial ripening agents or added colours.'],
        ['How do you pack the fruits to keep them fresh?', 'Each order is hand-packed in ventilated, cushioned boxes designed for fresh produce, to prevent bruising and keep the fruit fresh in transit.'],
        ['Is there a minimum order or bulk pricing?', 'No minimum order for regular orders. For bulk or B2B orders, message us on WhatsApp for wholesale pricing.'],
        ['What if my fruits arrive damaged?', 'Message us on WhatsApp with a photo within 24 hours of delivery and we\'ll arrange a replacement or refund.'],
        ['How do I track my order?', 'You\'ll get updates and a delivery confirmation on WhatsApp automatically. You can also track your order anytime using your order number and phone number.'],
    ];

    $itemsToShow = isset($limit) ? array_slice($faqItems, 0, $limit) : $faqItems;
@endphp

<div class="space-y-3">
    @foreach($itemsToShow as [$q, $a])
        <details class="group bg-amber-50 border border-amber-100 rounded-2xl p-5">
            <summary class="flex items-center justify-between cursor-pointer font-extrabold text-sm text-stone-800 list-none">
                {{ $q }}
                <svg class="w-4 h-4 text-amber-500 transition-transform group-open:rotate-45" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
            </summary>
            <p class="text-sm text-stone-600 mt-3 leading-relaxed">{{ $a }}</p>
        </details>
    @endforeach
</div>
