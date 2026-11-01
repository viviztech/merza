<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ApiOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $secret = ApiKey::generateSecret();
        ApiKey::create([
            'name' => 'Order management test',
            'key_prefix' => ApiKey::prefixFor($secret),
            'key_hash' => ApiKey::hashSecret($secret),
            'abilities' => [ApiKey::ABILITY_ORDERS_WRITE],
        ]);

        $this->withToken($secret);
    }

    public function test_partial_updates_reject_null_lifecycle_fields_without_changing_the_order(): void
    {
        $order = $this->createOrder();

        $this->patchJson("/api/v1/orders/{$order->id}", [
            'status' => null,
            'payment_status' => null,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'payment_status']);

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('unpaid', $order->fresh()->payment_status);

        $this->patchJson("/api/v1/orders/{$order->id}", ['notes' => 'Updated by integration'])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_status', 'unpaid')
            ->assertJsonPath('data.notes', 'Updated by integration');
    }

    public function test_postcode_validation_matches_the_database_limit(): void
    {
        $payload = $this->orderPayload([
            ['product_name' => 'Custom box', 'unit_price' => 100, 'quantity' => 1],
        ]);
        $payload['postcode'] = str_repeat('1', 11);

        $this->postJson('/api/v1/orders', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('postcode');

        $this->assertDatabaseCount('orders', 0);

        $payload['postcode'] = str_repeat('1', 10);
        $this->postJson('/api/v1/orders', $payload)
            ->assertCreated()
            ->assertJsonPath('data.postcode', $payload['postcode']);
    }

    public function test_courier_name_validation_matches_the_database_limit(): void
    {
        $order = $this->createOrder();

        $this->patchJson("/api/v1/orders/{$order->id}", ['courier_name' => str_repeat('C', 81)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('courier_name');

        $courierName = str_repeat('C', 80);
        $this->patchJson("/api/v1/orders/{$order->id}", ['courier_name' => $courierName])
            ->assertOk()
            ->assertJsonPath('data.courier_name', $courierName);
    }

    public function test_unknown_catalog_selectors_are_rejected_before_creating_an_order(): void
    {
        $this->postJson('/api/v1/orders', $this->orderPayload([
            ['product_variant_id' => 999999, 'quantity' => 1],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.product_variant_id');

        $this->postJson('/api/v1/orders', $this->orderPayload([
            ['sku' => 'UNKNOWN-SKU', 'product_name' => 'Custom box', 'unit_price' => 100, 'quantity' => 1],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.sku');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        Queue::assertNothingPushed();
    }

    public function test_conflicting_product_variant_and_sku_are_rejected(): void
    {
        $firstVariant = $this->createVariant('API-FIRST');
        $secondVariant = $this->createVariant('API-SECOND');

        $this->postJson('/api/v1/orders', $this->orderPayload([
            [
                'product_variant_id' => $firstVariant->id,
                'sku' => $secondVariant->sku,
                'quantity' => 1,
            ],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.sku');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_custom_items_require_an_explicit_name_and_price(): void
    {
        $this->postJson('/api/v1/orders', $this->orderPayload([
            ['quantity' => 1],
            ['product_name' => 'Unpriced box', 'quantity' => 1],
            ['unit_price' => 100, 'quantity' => 1],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.0.product_name',
                'items.0.unit_price',
                'items.1.unit_price',
                'items.2.product_name',
            ]);

        $this->assertDatabaseCount('orders', 0);

        $payload = $this->orderPayload([
            ['product_name' => 'Custom box', 'unit_price' => 125, 'quantity' => 2],
            ['product_name' => 'Complimentary sample', 'unit_price' => 0, 'quantity' => 1],
        ]);
        $payload['delivery_fee'] = 20;
        $payload['packaging_fee'] = 5;

        $this->postJson('/api/v1/orders', $payload)
            ->assertCreated()
            ->assertJsonPath('data.total', '275.00')
            ->assertJsonPath('data.items.0.product_variant_id', null)
            ->assertJsonPath('data.items.0.product_name', 'Custom box')
            ->assertJsonPath('data.items.1.unit_price', '0.00');
    }

    public function test_catalog_items_keep_prices_tax_and_preorder_metadata_for_each_selector(): void
    {
        $variant = $this->createVariant('API-PREORDER', [
            'is_preorder' => true,
            'available_from' => '2026-11-01',
        ]);

        $response = $this->postJson('/api/v1/orders', $this->orderPayload([
            ['product_variant_id' => $variant->id, 'quantity' => 1],
            ['sku' => $variant->sku, 'quantity' => 1],
            ['product_variant_id' => $variant->id, 'sku' => $variant->sku, 'quantity' => 1],
        ]))
            ->assertCreated()
            ->assertJsonPath('data.subtotal', '354.00')
            ->assertJsonPath('data.gst_total', '54.00')
            ->assertJsonPath('data.total', '354.00');

        $order = Order::with('items')->findOrFail($response->json('data.id'));
        $this->assertTrue($order->isPreorderOnly());
        $this->assertCount(3, $order->items);

        foreach ($order->items as $item) {
            $this->assertSame($variant->id, $item->product_variant_id);
            $this->assertSame('118.00', $item->unit_price);
            $this->assertSame('18.00', $item->gst_amount);
            $this->assertTrue($item->is_preorder);
            $this->assertSame('2026-11-01', $item->available_from->toDateString());
        }
    }

    private function createOrder(): Order
    {
        return Order::create([
            'customer_name' => 'API Customer',
            'customer_phone' => '9876543210',
            'delivery_address' => '123 Market Road',
        ]);
    }

    private function orderPayload(array $items): array
    {
        return [
            'customer_name' => 'API Customer',
            'customer_phone' => '9876543210',
            'delivery_address' => '123 Market Road',
            'items' => $items,
        ];
    }

    private function createVariant(string $sku, array $productData = []): ProductVariant
    {
        $category = Category::firstOrCreate(['slug' => 'api-fruits'], ['name' => 'API Fruits']);
        $product = Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'API Fruit Box',
            'slug' => strtolower($sku),
            'base_price' => 118,
            'gst_rate' => 18,
            'is_active' => true,
        ], $productData));

        return ProductVariant::create([
            'product_id' => $product->id,
            'name' => '1 kg box',
            'sku' => $sku,
            'price' => 118,
            'stock_qty' => 20,
            'is_active' => true,
        ]);
    }
}
