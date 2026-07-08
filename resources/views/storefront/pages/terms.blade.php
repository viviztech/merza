<x-layouts.storefront title="Terms & Conditions" description="Merza's terms and conditions — your rights and obligations when shopping with us.">

    <section class="max-w-3xl mx-auto px-4 py-16">

        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-stone-900 mb-2">Terms &amp; Conditions</h1>
            <p class="text-stone-400 text-sm">Last updated: {{ date('d F Y') }}</p>
        </div>

        <div class="prose prose-stone prose-sm max-w-none space-y-8">

            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
                By placing an order or using the Merza website, you agree to these Terms &amp; Conditions. Please read them carefully before purchasing. These terms are governed by the laws of India.
            </div>

            @foreach([
                [
                    'title' => '1. About Merza',
                    'content' => 'Merza Bodi ("Merza", "we", "us", "our") is an Indian tropical fruit retailer operating at merzabodi.com. Our registered business address is No.9, Jeeva Nagar North Street, Bodinayakanur, Theni - 625513, Tamil Nadu, India. Contact us at <a href="mailto:merzabodinayakanur@gmail.com" class="text-amber-700 underline">merzabodinayakanur@gmail.com</a> or WhatsApp +91 86676 96278.',
                ],
                [
                    'title' => '2. Products & Orders',
                    'content' => '<ul class="list-disc list-inside mt-2 space-y-1 text-stone-600">
                        <li>All products are subject to availability. We reserve the right to cancel orders if a product is out of stock or pricing errors occur.</li>
                        <li>Product images are for illustration only. Actual colour, size, and appearance of fresh fruit may vary by season and variety.</li>
                        <li>Orders are confirmed once you receive a WhatsApp or email confirmation from us. Placing an order does not guarantee availability.</li>
                        <li>We reserve the right to reject or cancel any order at our discretion, with a full refund where payment has been made.</li>
                    </ul>',
                ],
                [
                    'title' => '3. Pricing',
                    'content' => 'All prices are listed in Indian Rupees (₹) and are inclusive of applicable taxes unless stated otherwise. Delivery charges, if any, are shown at checkout. We reserve the right to change prices at any time without prior notice. Prices at the time of order confirmation are binding.',
                ],
                [
                    'title' => '4. Payment',
                    'content' => 'We accept the following payment methods:
                    <ul class="list-disc list-inside mt-2 space-y-1 text-stone-600">
                        <li><strong>Cash on Delivery (COD)</strong> — payment collected upon delivery</li>
                        <li><strong>Bank Transfer / UPI</strong> — payment details provided after order confirmation</li>
                        <li><strong>WhatsApp Payment</strong> — via WhatsApp Pay where available</li>
                    </ul>
                    <p class="mt-2">For prepaid orders, goods will only be dispatched after payment is received and confirmed. We do not store any payment card details.</p>',
                ],
                [
                    'title' => '5. Delivery',
                    'content' => '<ul class="list-disc list-inside mt-2 space-y-1 text-stone-600">
                        <li>Delivery timelines are estimates only and not guaranteed. We are not liable for delays caused by third-party logistics partners, weather, or other factors beyond our control.</li>
                        <li>Delivery is available to serviceable pincodes. Orders outside our delivery area may be cancelled with a full refund.</li>
                        <li>Risk of loss or damage passes to you upon delivery to the address provided.</li>
                        <li>You are responsible for providing an accurate delivery address. We are not liable for failed deliveries due to incorrect addresses.</li>
                    </ul>',
                ],
                [
                    'title' => '6. Returns & Refunds',
                    'content' => 'Fresh produce is perishable. We do not accept returns. However:
                    <ul class="list-disc list-inside mt-2 space-y-1 text-stone-600">
                        <li>If you receive a product that is damaged, spoiled, or significantly different from what was ordered, contact us within <strong>24 hours of delivery</strong> with photos via WhatsApp or email.</li>
                        <li>At our discretion, we may offer a replacement, store credit, or refund.</li>
                        <li>Refunds, where approved, will be processed within 5–7 business days to the original payment method or via bank transfer.</li>
                        <li>COD orders that are refused at delivery without a valid reason may incur a handling fee.</li>
                    </ul>',
                ],
                [
                    'title' => '7. Cancellations',
                    'content' => 'You may cancel an order by contacting us via WhatsApp or email <strong>before the order is dispatched</strong>. Once dispatched, cancellations cannot be accepted. For prepaid orders cancelled before dispatch, a full refund will be processed within 5–7 business days.',
                ],
                [
                    'title' => '8. Intellectual Property',
                    'content' => 'All content on this website — including text, images, logos, product descriptions, and branding — is the property of Merza Bodi and is protected by applicable intellectual property laws. You may not reproduce, copy, or use any content without our express written permission.',
                ],
                [
                    'title' => '9. Limitation of Liability',
                    'content' => 'To the maximum extent permitted by law, Merza shall not be liable for any indirect, incidental, or consequential loss arising from the use of our website or products. Our total liability for any claim is limited to the amount paid for the relevant order. Nothing in these terms limits our liability for death or personal injury caused by our negligence.',
                ],
                [
                    'title' => '10. Governing Law',
                    'content' => 'These Terms &amp; Conditions are governed by and construed in accordance with the laws of India. Any disputes arising from these terms shall be subject to the exclusive jurisdiction of the courts of Theni, Tamil Nadu, India.',
                ],
                [
                    'title' => '11. Changes to These Terms',
                    'content' => 'We may update these Terms &amp; Conditions from time to time. The "Last updated" date at the top of this page reflects the most recent revision. Continued use of our website or services after changes constitutes your acceptance of the revised terms.',
                ],
                [
                    'title' => '12. Contact Us',
                    'content' => 'For any questions about these Terms &amp; Conditions:
                    <div class="mt-2 space-y-1 text-stone-600">
                        <p>📧 <a href="mailto:merzabodinayakanur@gmail.com" class="text-amber-700 underline">merzabodinayakanur@gmail.com</a></p>
                        <p>📞 +91 86676 96278</p>
                        <p>📍 No.9, Jeeva Nagar North Street, Bodinayakanur, Theni - 625513, Tamil Nadu, India</p>
                    </div>',
                ],
            ] as $section)
                <div class="border-b border-stone-100 pb-6 last:border-0">
                    <h2 class="text-base font-bold text-stone-900 mb-3">{{ $section['title'] }}</h2>
                    <div class="text-sm text-stone-600 leading-relaxed">{!! $section['content'] !!}</div>
                </div>
            @endforeach

        </div>
    </section>

</x-layouts.storefront>
