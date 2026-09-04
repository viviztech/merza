<?php

namespace App\Support;

class FaqData
{
    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function items(): array
    {
        return [
            ['Which areas do you deliver to?', 'We deliver across Tamil Nadu and to select cities nationwide — see the Delivery Information section on the homepage for zones and rates, or message us on WhatsApp to check your area.'],
            ['What payment methods do you accept?', 'UPI (Google Pay, PhonePe, or any UPI app via QR code) and Cash on Delivery, where available. Card payments are coming soon.'],
            ['Are your fruits naturally ripened?', 'Yes — all our fruits are grown on our own farm in Bodinayakanur and naturally ripened, with no artificial ripening agents or added colours.'],
            ['How do you pack the fruits to keep them fresh?', 'Each order is hand-packed in ventilated, cushioned boxes designed for fresh produce, to prevent bruising and keep the fruit fresh in transit.'],
            ['Is there a minimum order or bulk pricing?', 'No minimum order for regular orders. For bulk or B2B orders, message us on WhatsApp for wholesale pricing.'],
            ['What if my fruits arrive damaged?', 'Message us on WhatsApp with a photo within 24 hours of delivery and we\'ll arrange a replacement or refund.'],
            ['How do I track my order?', 'You\'ll get updates and a delivery confirmation on WhatsApp automatically. You can also track your order anytime using your order number and phone number.'],
        ];
    }

    /**
     * Build FAQPage JSON-LD (schema.org) from the same source used for the visible accordion,
     * so the structured data can never drift out of sync with what's on the page.
     *
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(
                fn (array $item) => [
                    '@type' => 'Question',
                    'name' => $item[0],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $item[1],
                    ],
                ],
                self::items()
            ),
        ];
    }
}
