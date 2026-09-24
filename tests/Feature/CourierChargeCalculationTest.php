<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DeliverySetting;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\DeliveryCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierChargeCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_gram_packet_uses_minimum_courier_weight(): void
    {
        $variant = $this->variant(weight: 250, unit: 'g');
        app(CartService::class)->add($variant->id);

        $zone = $this->zone(rate: 60);
        $breakdown = (new DeliveryCalculatorService)->calculateForZone(
            $zone,
            app(CartService::class)->totalWeightKg(),
        );

        $this->assertSame(0.25, app(CartService::class)->totalWeightKg());
        $this->assertSame(1.0, $breakdown['chargeable_weight']);
        $this->assertSame(60.0, $breakdown['shipping_cost']);
        $this->assertSame(110.0, $breakdown['total_fee']);
    }

    public function test_courier_weight_rounds_up_to_configured_slab(): void
    {
        DeliverySetting::current()->update([
            'minimum_chargeable_weight_kg' => 0.5,
            'billing_weight_step_kg' => 0.5,
        ]);

        $breakdown = (new DeliveryCalculatorService)->calculateForZone($this->zone(rate: 40), 1.2);

        $this->assertSame(1.5, $breakdown['chargeable_weight']);
        $this->assertSame(60.0, $breakdown['shipping_cost']);
    }

    public function test_piece_variant_uses_explicit_courier_weight(): void
    {
        $variant = $this->variant(weight: 1, unit: 'pcs', shippingWeight: 0.35);
        app(CartService::class)->add($variant->id, 2);

        $this->assertSame(0.7, app(CartService::class)->totalWeightKg());
    }

    private function variant(float $weight, string $unit, ?float $shippingWeight = null): ProductVariant
    {
        $category = Category::create([
            'name' => 'Packets',
            'slug' => 'packets-'.str()->random(6),
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Test Packet',
            'slug' => 'test-packet-'.str()->random(6),
            'base_price' => 100,
            'unit' => $unit,
            'is_active' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'name' => $weight.$unit,
            'sku' => 'PKT-'.str()->random(6),
            'price' => 100,
            'weight_value' => $weight,
            'weight_unit' => $unit,
            'shipping_weight_kg' => $shippingWeight,
            'stock_qty' => 10,
            'is_active' => true,
        ]);
    }

    private function zone(float $rate): DeliveryZone
    {
        return DeliveryZone::create([
            'name' => 'Test Zone',
            'match_type' => 'state',
            'match_values' => ['Tamil Nadu'],
            'rate_per_kg' => $rate,
            'is_active' => true,
        ]);
    }
}
