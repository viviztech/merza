<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $freshCat = Category::firstOrCreate(
            ['slug' => 'fresh-fruits'],
            ['name' => 'Fresh Fruits', 'icon' => 'heroicon-o-sun', 'is_active' => true]
        );

        $data = [
            'category'          => $freshCat,
            'name'              => 'Kasa Lattu Mangoes',
            'short_description' => 'Sweet, fibre-free Kasa Lattu mangoes from Tamil Nadu — available in bulk packs.',
            'description'       => '<p>Kasa Lattu is a prized mango variety from Tamil Nadu, known for its rich sweetness, smooth fibre-free flesh, and distinctive aroma. Sourced fresh and packed in bulk for families and bulk buyers alike.</p>',
            'base_price'        => 100.00,
            'unit'              => 'kg',
            'is_featured'       => true,
            'variants'          => [
                ['name' => '5 kg',  'price' => 500.00,  'free_gift_label' => null,             'weight_value' => 5.0,  'weight_unit' => 'kg', 'stock_qty' => 50, 'sku' => 'KLM-5KG'],
                ['name' => '10 kg', 'price' => 950.00,  'free_gift_label' => 'Free 1kg Mango',  'weight_value' => 10.0, 'weight_unit' => 'kg', 'stock_qty' => 30, 'sku' => 'KLM-10KG'],
                ['name' => '15 kg', 'price' => 1350.00, 'free_gift_label' => null,             'weight_value' => 15.0, 'weight_unit' => 'kg', 'stock_qty' => 20, 'sku' => 'KLM-15KG'],
            ],
        ];

        $product = Product::firstOrCreate(
            ['slug' => Str::slug($data['name'])],
            [
                'category_id'       => $data['category']->id,
                'name'              => $data['name'],
                'slug'              => Str::slug($data['name']),
                'short_description' => $data['short_description'],
                'description'       => $data['description'],
                'base_price'        => $data['base_price'],
                'unit'              => $data['unit'],
                'is_active'         => true,
                'is_featured'       => $data['is_featured'],
                'sort_order'        => 0,
            ]
        );

        foreach ($data['variants'] as $varIndex => $v) {
            ProductVariant::updateOrCreate(
                ['sku' => $v['sku']],
                [
                    'product_id'          => $product->id,
                    'name'                => $v['name'],
                    'sku'                 => $v['sku'],
                    'price'               => $v['price'],
                    'free_gift_label'     => $v['free_gift_label'],
                    'weight_value'        => $v['weight_value'],
                    'weight_unit'         => $v['weight_unit'],
                    'stock_qty'           => $v['stock_qty'],
                    'low_stock_threshold' => 10,
                    'is_active'           => true,
                    'sort_order'          => $varIndex,
                ]
            );
        }

        $this->command->info('Seeded Kasa Lattu Mangoes (5kg / 10kg / 15kg).');
    }
}
