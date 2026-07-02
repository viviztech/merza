<x-layouts.storefront title="Privacy Policy" description="Merza's privacy policy — how we collect, use and protect your personal data in compliance with Malaysia's PDPA 2010.">

    <section class="max-w-3xl mx-auto px-4 py-16">

        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-stone-900 mb-2">Privacy Policy</h1>
            <p class="text-stone-400 text-sm">Last updated: {{ date('d F Y') }}</p>
        </div>

        <div class="prose prose-stone prose-sm max-w-none space-y-8">

            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
                This Privacy Policy is prepared in accordance with the <strong>Personal Data Protection Act 2010 (PDPA)</strong> of Malaysia. By using our website or placing an order with Merza, you agree to the practices described here.
            </div>

            @foreach([
                [
                    'title' => '1. Who We Are',
                    'content' => 'Merza Bodi ("Merza", "we", "us", "our") is a Malaysian tropical fruit retailer operating at merzabodi.com. Our business address is Kuala Lumpur, Malaysia. For any privacy-related enquiries, contact us at <a href="mailto:hello@merzabodi.com" class="text-amber-700 underline">hello@merzabodi.com</a> or via WhatsApp at +60 12-345 6789.',
                ],
                [
                    'title' => '2. Personal Data We Collect',
                    'content' => 'When you place an order or contact us, we may collect:
                    <ul class="list-disc list-inside mt-2 space-y-1 text-stone-600">
                        <li>Full name</li>
                        <li>Phone number (mobile/WhatsApp)</li>
                        <li>Email address</li>
                        <li>Delivery address</li>
                        <li>Order history and product preferences</li>
                        <li>WhatsApp/Facebook messages sent to us</li>
                    </ul>
                    <p class="mt-2">We do not collect payment card details. All payments are processed via bank transfer or cash on delivery.</p>',
                ],
                [
                    'title' => '3. How We Use Your Data',
                    'content' => 'We use your personal data to:
                    <ul class="list-disc list-inside mt-2 space-y-1 text-stone-600">
                        <li>Process and fulfil your orders</li>
                        <li>Communicate delivery updates via WhatsApp or phone</li>
                        <li>Respond to your enquiries and support requests</li>
                        <li>Send you promotions or product updates (only if you have opted in)</li>
                        <li>Improve our products and services</li>
                        <li>Meet legal and regulatory requirements</li>
                    </ul>',
                ],
                [
                    'title' => '4. Data Sharing',
                    'content' => 'We do not sell your personal data. We may share limited data with:
                    <ul class="list-disc list-inside mt-2 space-y-1 text-stone-600">
                        <li><strong>Delivery partners</strong> — name, phone, and address only, for the purpose of completing your delivery.</li>
                        <li><strong>Service providers</strong> — technology platforms (cloud hosting, messaging) that help us operate. These providers are contractually bound to keep your data confidential.</li>
                        <li><strong>Legal authorities</strong> — if required by Malaysian law or a valid court order.</li>
                    </ul>',
                ],
                [
                    'title' => '5. Data Retention',
                    'content' => 'We retain your personal data for as long as necessary to fulfil the purposes stated in this policy, or as required by law. Order records are kept for a minimum of 7 years for accounting and compliance purposes. WhatsApp conversation history is retained within the WhatsApp platform subject to their terms.',
                ],
                [
                    'title' => '6. Your Rights Under PDPA',
                    'content' => 'As a data subject under the PDPA 2010, you have the right to:
                    <ul class="list-disc list-inside mt-2 space-y-1 text-stone-600">
                        <li>Access the personal data we hold about you</li>
                        <li>Correct inaccurate or incomplete data</li>
                        <li>Withdraw consent for marketing communications at any time</li>
                        <li>Request deletion of your data (subject to legal retention requirements)</li>
                    </ul>
                    <p class="mt-2">To exercise any of these rights, contact us at <a href="mailto:hello@merzabodi.com" class="text-amber-700 underline">hello@merzabodi.com</a> or WhatsApp +60 12-345 6789. We will respond within 14 business days.</p>',
                ],
                [
                    'title' => '7. Cookies',
                    'content' => 'Our website uses essential cookies to operate correctly (session management, cart functionality). We do not use third-party advertising or tracking cookies. You may disable cookies in your browser settings, but this may affect website functionality.',
                ],
                [
                    'title' => '8. Security',
                    'content' => 'We use HTTPS encryption for all data transmitted through our website. Your data is stored on secure servers. We limit access to your personal data to employees and partners who need it to fulfil your order. No system is 100% secure — if you become aware of any security issue, please notify us immediately.',
                ],
                [
                    'title' => '9. Third-Party Links',
                    'content' => 'Our website may contain links to third-party platforms (WhatsApp, Facebook, Instagram). We are not responsible for the privacy practices of those platforms. Please review their respective privacy policies.',
                ],
                [
                    'title' => '10. Changes to This Policy',
                    'content' => 'We may update this policy from time to time. The "Last updated" date at the top of this page reflects the most recent revision. Continued use of our website after changes constitutes acceptance of the updated policy.',
                ],
                [
                    'title' => '11. Contact Us',
                    'content' => 'For any questions about this Privacy Policy or how we handle your data:
                    <div class="mt-2 space-y-1 text-stone-600">
                        <p>📧 <a href="mailto:hello@merzabodi.com" class="text-amber-700 underline">hello@merzabodi.com</a></p>
                        <p>📞 +60 12-345 6789</p>
                        <p>📍 Kuala Lumpur, Malaysia</p>
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
