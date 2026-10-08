<?php

namespace App\Support;

class RecipeData
{
    public static function all(): array
    {
        return [
            'amla-ginger-chutney' => [
                'title' => 'Fresh Amla and Ginger Chutney',
                'summary' => 'A tangy chutney made with fresh amla, ginger and green chilli. Serve in small portions with rice or dosa.',
                'product_search' => 'amla',
                'time' => '20 minutes',
                'ingredients' => ['4 fresh amla', '1 small piece of ginger', '1 green chilli', '2 tablespoons grated coconut', 'Salt to taste', 'Water as needed'],
                'steps' => [
                    'Wash the amla, remove the seeds and cut the flesh into small pieces.',
                    'Blend the amla with ginger, chilli, coconut, salt and a little water until spoonable.',
                    'Taste and adjust the salt. Refrigerate any leftovers promptly in a covered container.',
                ],
            ],
            'sapota-yogurt-bowl' => [
                'title' => 'Sapota and Yogurt Breakfast Bowl',
                'summary' => 'A simple breakfast bowl with ripe sapota, plain yogurt and toasted nuts.',
                'product_search' => 'sapota',
                'time' => '10 minutes',
                'ingredients' => ['2 ripe sapota', '1 cup plain yogurt', '2 tablespoons chopped toasted nuts', 'A pinch of cardamom (optional)'],
                'steps' => [
                    'Wash, peel and deseed the sapota. Slice the flesh just before serving.',
                    'Spoon the yogurt into two bowls and top with the sapota.',
                    'Scatter over the nuts and optional cardamom. Serve immediately.',
                ],
            ],
            'red-banana-chip-trail-mix' => [
                'title' => 'Red Banana Chip Trail Mix',
                'summary' => 'A quick snack mix using freeze-dried red banana chips, nuts and seeds.',
                'product_search' => 'red banana chips',
                'time' => '5 minutes',
                'ingredients' => ['1 cup freeze-dried red banana chips', '½ cup roasted unsalted peanuts or almonds', '¼ cup pumpkin seeds', '2 tablespoons coconut flakes (optional)'],
                'steps' => [
                    'Combine the banana chips, nuts, seeds and optional coconut in a bowl.',
                    'Portion into small containers. Keep the remaining mix sealed and follow the chip packet’s storage directions.',
                ],
            ],
        ];
    }
}
