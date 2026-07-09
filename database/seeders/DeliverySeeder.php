<?php

namespace Database\Seeders;

use App\Models\DeliverySetting;
use App\Models\DeliveryZone;
use Illuminate\Database\Seeder;

class DeliverySeeder extends Seeder
{
    public function run(): void
    {
        // Seed default settings (single row)
        DeliverySetting::firstOrCreate([], [
            'packing_charge'           => 50,
            'packing_weight_kg'        => 1,
            'free_weight_threshold_kg' => 5,
            'free_weight_kg'           => 1,
            'is_active'                => true,
        ]);

        $zones = [
            [
                'name'         => 'Tamil Nadu',
                'match_type'   => 'state',
                'match_values' => ['Tamil Nadu', 'Tamilnadu', 'TN'],
                'rate_per_kg'  => 20,
                'sort_order'   => 1,
            ],
            [
                'name'         => 'Kerala',
                'match_type'   => 'state',
                'match_values' => ['Kerala', 'KL'],
                'rate_per_kg'  => 40,
                'sort_order'   => 2,
            ],
            [
                'name'         => 'Bangalore',
                'match_type'   => 'city',
                'match_values' => ['Bangalore', 'Bengaluru', 'Bengalore'],
                'rate_per_kg'  => 40,
                'sort_order'   => 3,
            ],
            [
                'name'         => 'Hyderabad',
                'match_type'   => 'city',
                'match_values' => ['Hyderabad', 'Secunderabad'],
                'rate_per_kg'  => 60,
                'sort_order'   => 4,
            ],
        ];

        foreach ($zones as $zone) {
            DeliveryZone::firstOrCreate(
                ['name' => $zone['name']],
                array_merge($zone, ['is_active' => true])
            );
        }

        $this->command->info('Seeded delivery zones and settings.');
    }
}
