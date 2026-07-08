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
        $categories = [
            ['name' => 'Fresh Fruits',   'icon' => 'heroicon-o-sun'],
            ['name' => 'Processed',      'icon' => 'heroicon-o-beaker'],
            ['name' => 'Bulk / B2B',     'icon' => 'heroicon-o-building-storefront'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['slug' => Str::slug($cat['name'])],
                array_merge($cat, ['slug' => Str::slug($cat['name']), 'is_active' => true])
            );
        }

        $freshCat     = Category::where('slug', 'fresh-fruits')->first();
        $processedCat = Category::where('slug', 'processed')->first();

        $products = [
            [
                'category'          => $freshCat,
                'name'              => 'Premium Mangoes',
                'short_description' => 'Juicy Alphonso & Harum Manis mangoes, hand-picked at peak ripeness.',
                'description'       => '<p>Merza Premium Mangoes are sourced from certified farms. Available in Alphonso and Harum Manis varieties. Perfect sweetness, zero artificial ripening.</p>',
                'base_price'        => 18.00,
                'unit'              => 'kg',
                'is_featured'       => true,
                'variants'          => [
                    ['name' => '500g', 'price' => 9.00,  'weight_value' => 0.5, 'weight_unit' => 'kg', 'stock_qty' => 120, 'sku' => 'MNG-500G'],
                    ['name' => '1 kg', 'price' => 18.00, 'weight_value' => 1.0, 'weight_unit' => 'kg', 'stock_qty' => 85,  'sku' => 'MNG-1KG'],
                    ['name' => '3 kg', 'price' => 50.00, 'weight_value' => 3.0, 'weight_unit' => 'kg', 'stock_qty' => 40,  'sku' => 'MNG-3KG'],
                    ['name' => '5 kg', 'price' => 80.00, 'weight_value' => 5.0, 'weight_unit' => 'kg', 'stock_qty' => 20,  'sku' => 'MNG-5KG'],
                ],
            ],
            [
                'category'          => $freshCat,
                'name'              => 'Banana Red',
                'short_description' => 'Rare red-skinned bananas with a creamy, sweet flavour. Limited supply.',
                'description'       => '<p>Banana Red (Pisang Merah) is a premium variety with a distinctive reddish skin and rich flavour profile. High in potassium and antioxidants.</p>',
                'base_price'        => 12.00,
                'unit'              => 'kg',
                'is_featured'       => true,
                'variants'          => [
                    ['name' => '500g', 'price' => 7.00,  'weight_value' => 0.5, 'weight_unit' => 'kg', 'stock_qty' => 60,  'sku' => 'BNR-500G'],
                    ['name' => '1 kg', 'price' => 12.00, 'weight_value' => 1.0, 'weight_unit' => 'kg', 'stock_qty' => 45,  'sku' => 'BNR-1KG'],
                    ['name' => '3 kg', 'price' => 33.00, 'weight_value' => 3.0, 'weight_unit' => 'kg', 'stock_qty' => 15,  'sku' => 'BNR-3KG'],
                ],
            ],
            [
                'category'          => $freshCat,
                'name'              => 'Vietnam Gold Jackfruit',
                'short_description' => 'Premium Vietnamese golden jackfruit — crispy, sweet, and aromatic.',
                'description'       => '<p>Vietnam Gold Jackfruit is a premium variety known for its golden-yellow flesh, crispy texture, and natural sweetness. Sourced directly from Vietnamese farms.</p>',
                'base_price'        => 25.00,
                'unit'              => 'kg',
                'is_featured'       => true,
                'variants'          => [
                    ['name' => '1 kg',    'price' => 25.00,  'weight_value' => 1.0,  'weight_unit' => 'kg', 'stock_qty' => 30,  'sku' => 'JFR-1KG'],
                    ['name' => '3 kg',    'price' => 70.00,  'weight_value' => 3.0,  'weight_unit' => 'kg', 'stock_qty' => 20,  'sku' => 'JFR-3KG'],
                    ['name' => 'Whole (~8kg)', 'price' => 160.00, 'weight_value' => 8.0, 'weight_unit' => 'kg', 'stock_qty' => 8, 'sku' => 'JFR-WHOLE'],
                ],
            ],
            [
                'category'          => $freshCat,
                'name'              => 'Kasa Lattu Mangoes',
                'short_description' => 'Sweet, fibre-free Kasa Lattu mangoes from Tamil Nadu — available in bulk packs.',
                'description'       => '<p>Kasa Lattu is a prized mango variety from Tamil Nadu, known for its rich sweetness, smooth fibre-free flesh, and distinctive aroma. Sourced fresh and packed in bulk for families and bulk buyers alike.</p>',
                'base_price'        => 100.00,
                'unit'              => 'kg',
                'is_featured'       => true,
                'variants'          => [
                    ['name' => '5 kg',  'price' => 500.00,  'weight_value' => 5.0,  'weight_unit' => 'kg', 'stock_qty' => 50, 'sku' => 'KLM-5KG'],
                    ['name' => '10 kg', 'price' => 950.00,  'weight_value' => 10.0, 'weight_unit' => 'kg', 'stock_qty' => 30, 'sku' => 'KLM-10KG'],
                    ['name' => '15 kg', 'price' => 1350.00, 'weight_value' => 15.0, 'weight_unit' => 'kg', 'stock_qty' => 20, 'sku' => 'KLM-15KG'],
                ],
            ],
            [
                'category'          => $processedCat,
                'name'              => 'Freeze Dried Fruits',
                'short_description' => 'Crispy freeze-dried mango and jackfruit slices. Long shelf life, zero additives.',
                'description'       => '<p>Our freeze-dried fruits retain 97% of their nutritional value and natural flavour. No preservatives, no artificial colours. Perfect as a healthy snack or for gifting.</p>',
                'base_price'        => 15.00,
                'unit'              => 'pack',
                'is_featured'       => false,
                'variants'          => [
                    ['name' => 'Mango 50g',       'price' => 15.00, 'weight_value' => 50,  'weight_unit' => 'g', 'stock_qty' => 100, 'sku' => 'FD-MNG-50G'],
                    ['name' => 'Mango 100g',      'price' => 28.00, 'weight_value' => 100, 'weight_unit' => 'g', 'stock_qty' => 80,  'sku' => 'FD-MNG-100G'],
                    ['name' => 'Jackfruit 50g',   'price' => 15.00, 'weight_value' => 50,  'weight_unit' => 'g', 'stock_qty' => 70,  'sku' => 'FD-JFR-50G'],
                    ['name' => 'Jackfruit 100g',  'price' => 28.00, 'weight_value' => 100, 'weight_unit' => 'g', 'stock_qty' => 50,  'sku' => 'FD-JFR-100G'],
                    ['name' => 'Mixed Gift Box',  'price' => 55.00, 'weight_value' => 250, 'weight_unit' => 'g', 'stock_qty' => 30,  'sku' => 'FD-GIFT-250G'],
                ],
            ],
            [
                'category'          => $processedCat,
                'name'              => 'Fruit Pulp',
                'short_description' => 'Pure mango and jackfruit pulp — no sugar, no water added. Ready for drinks, desserts & cooking.',
                'description'       => '<p>Merza Fruit Pulp is made from 100% fresh tropical fruits, processed within hours of harvest. No added sugar, no preservatives. Perfect for beverages, ice cream, and culinary use.</p>',
                'base_price'        => 20.00,
                'unit'              => 'pack',
                'is_featured'       => false,
                'variants'          => [
                    ['name' => 'Mango Pulp 500g',     'price' => 20.00, 'weight_value' => 0.5, 'weight_unit' => 'kg', 'stock_qty' => 60,  'sku' => 'PLP-MNG-500G'],
                    ['name' => 'Mango Pulp 1kg',      'price' => 38.00, 'weight_value' => 1.0, 'weight_unit' => 'kg', 'stock_qty' => 40,  'sku' => 'PLP-MNG-1KG'],
                    ['name' => 'Jackfruit Pulp 500g', 'price' => 22.00, 'weight_value' => 0.5, 'weight_unit' => 'kg', 'stock_qty' => 50,  'sku' => 'PLP-JFR-500G'],
                    ['name' => 'Jackfruit Pulp 1kg',  'price' => 40.00, 'weight_value' => 1.0, 'weight_unit' => 'kg', 'stock_qty' => 30,  'sku' => 'PLP-JFR-1KG'],
                ],
            ],
        ];

        foreach ($products as $index => $data) {
            $product = Product::firstOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'category_id'       => $data['category']?->id,
                    'name'              => $data['name'],
                    'slug'              => Str::slug($data['name']),
                    'short_description' => $data['short_description'],
                    'description'       => $data['description'],
                    'base_price'        => $data['base_price'],
                    'unit'              => $data['unit'],
                    'is_active'         => true,
                    'is_featured'       => $data['is_featured'],
                    'sort_order'        => $index,
                ]
            );

            foreach ($data['variants'] as $varIndex => $v) {
                ProductVariant::firstOrCreate(
                    ['sku' => $v['sku']],
                    [
                        'product_id'          => $product->id,
                        'name'                => $v['name'],
                        'sku'                 => $v['sku'],
                        'price'               => $v['price'],
                        'weight_value'        => $v['weight_value'],
                        'weight_unit'         => $v['weight_unit'],
                        'stock_qty'           => $v['stock_qty'],
                        'low_stock_threshold' => 10,
                        'is_active'           => true,
                        'sort_order'          => $varIndex,
                    ]
                );
            }
        }

        $this->command->info('Seeded 5 products with variants.');
    }
}
