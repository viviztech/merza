<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Lead;
use Illuminate\Database\Seeder;

class CrmSeeder extends Seeder
{
    public function run(): void
    {
        $contacts = [
            ['name' => 'Ahmad Farid',    'phone' => '60123456001', 'email' => 'farid@example.com',   'source' => 'meta_ads',  'city' => 'Kuala Lumpur', 'state' => 'W.P. Kuala Lumpur', 'is_customer' => true,  'tags' => ['vip','bulk-buyer']],
            ['name' => 'Siti Nurhaliza', 'phone' => '60123456002', 'email' => 'siti@example.com',    'source' => 'whatsapp',  'city' => 'Shah Alam',    'state' => 'Selangor',           'is_customer' => false, 'tags' => ['new-lead']],
            ['name' => 'Rajesh Kumar',   'phone' => '60123456003', 'email' => 'rajesh@example.com',  'source' => 'referral',  'city' => 'Johor Bahru',  'state' => 'Johor',              'is_customer' => true,  'tags' => ['b2b','reseller']],
            ['name' => 'Nurul Ain',      'phone' => '60123456004', 'email' => null,                  'source' => 'meta_ads',  'city' => 'Penang',       'state' => 'Pulau Pinang',       'is_customer' => false, 'tags' => ['interested']],
            ['name' => 'Tan Wei Liang',  'phone' => '60123456005', 'email' => 'tan@example.com',     'source' => 'website',   'city' => 'Petaling Jaya', 'state' => 'Selangor',          'is_customer' => false, 'tags' => ['cold']],
            ['name' => 'Fauziah Binti',  'phone' => '60123456006', 'email' => 'fauziah@example.com', 'source' => 'whatsapp',  'city' => 'Klang',        'state' => 'Selangor',           'is_customer' => true,  'tags' => ['regular']],
        ];

        foreach ($contacts as $c) {
            Contact::firstOrCreate(
                ['phone' => $c['phone']],
                array_merge($c, ['last_contacted_at' => now()->subDays(rand(1, 30))])
            );
        }

        $leadData = [
            ['contact' => '60123456001', 'stage' => 'converted',  'source' => 'meta_ads', 'product_interest' => 'Premium Mangoes 3kg', 'value' => 150.00, 'notes' => 'Bulk buyer, orders monthly'],
            ['contact' => '60123456002', 'stage' => 'interested', 'source' => 'whatsapp', 'product_interest' => 'Banana Red 1kg',      'value' => 12.00,  'notes' => 'Saw ad, wants to try'],
            ['contact' => '60123456003', 'stage' => 'quoted',     'source' => 'referral', 'product_interest' => 'Freeze Dried Mixed',  'value' => 275.00, 'notes' => 'Reseller, wants 20 boxes'],
            ['contact' => '60123456004', 'stage' => 'contacted',  'source' => 'meta_ads', 'product_interest' => 'Vietnam Gold Jackfruit 1kg', 'value' => 25.00, 'notes' => 'Clicked ad, replied on WhatsApp'],
            ['contact' => '60123456005', 'stage' => 'new',        'source' => 'website',  'product_interest' => 'Mango Pulp 1kg',      'value' => 38.00,  'notes' => 'Submitted contact form'],
            ['contact' => '60123456006', 'stage' => 'converted',  'source' => 'whatsapp', 'product_interest' => 'Premium Mangoes 5kg', 'value' => 80.00,  'notes' => 'Repeat customer'],
        ];

        foreach ($leadData as $l) {
            $contact = Contact::where('phone', $l['contact'])->first();
            if (!$contact) continue;

            Lead::firstOrCreate(
                ['contact_id' => $contact->id, 'stage' => $l['stage']],
                [
                    'source'           => $l['source'],
                    'product_interest' => $l['product_interest'],
                    'estimated_value'  => $l['value'],
                    'notes'            => $l['notes'],
                    'converted_at'     => $l['stage'] === 'converted' ? now()->subDays(rand(1, 14)) : null,
                    'due_at'           => in_array($l['stage'], ['new','contacted','interested']) ? now()->addDays(rand(1, 7)) : null,
                ]
            );
        }

        // Seed a few sample conversations
        $convData = [
            ['phone' => '60123456001', 'channel' => 'whatsapp', 'direction' => 'inbound',  'message' => 'Hi, I want to order mangoes. Do you have 3kg available?'],
            ['phone' => '60123456001', 'channel' => 'whatsapp', 'direction' => 'outbound', 'message' => 'Yes! We have Premium Mangoes 3kg at RM50. Would you like to order?'],
            ['phone' => '60123456001', 'channel' => 'whatsapp', 'direction' => 'inbound',  'message' => 'Yes please! Can I get 3 boxes?'],
            ['phone' => '60123456002', 'channel' => 'whatsapp', 'direction' => 'inbound',  'message' => 'I saw your ad on Facebook. How much is Banana Red?'],
            ['phone' => '60123456003', 'channel' => 'facebook', 'direction' => 'inbound',  'message' => 'We are a fruit shop. Can we get wholesale pricing for freeze-dried?'],
            ['phone' => '60123456004', 'channel' => 'whatsapp', 'direction' => 'outbound', 'message' => 'Hi! Thanks for your interest in our Vietnam Gold Jackfruit. It is RM25/kg.', 'is_bot' => true],
        ];

        foreach ($convData as $i => $c) {
            $contact = Contact::where('phone', $c['phone'])->first();
            if (!$contact) continue;

            Conversation::firstOrCreate(
                ['contact_id' => $contact->id, 'message' => $c['message']],
                [
                    'channel'   => $c['channel'],
                    'direction' => $c['direction'],
                    'status'    => 'read',
                    'is_bot'    => $c['is_bot'] ?? false,
                    'sent_at'   => now()->subMinutes(($i + 1) * 15),
                ]
            );
        }

        $this->command->info('Seeded 6 contacts, 6 leads, 6 conversations.');
    }
}
