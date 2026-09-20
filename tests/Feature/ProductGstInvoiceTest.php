<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\AdminOrderService;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductGstInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_gst_is_snapshotted_and_disclosed_without_changing_the_inclusive_total(): void
    {
        $category = Category::create([
            'name' => 'Fresh Fruits',
            'slug' => 'fresh-fruits-gst',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'GST Mango Box',
            'slug' => 'gst-mango-box',
            'base_price' => 118,
            'gst_rate' => 18,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => '1 kg box',
            'sku' => 'GST-MANGO-1KG',
            'price' => 118,
            'weight_value' => 1,
            'weight_unit' => 'kg',
            'stock_qty' => 10,
            'is_active' => true,
        ]);

        app(CartService::class)->add($variant->id);
        $this->assertSame(18.0, app(CartService::class)->gstTotal());

        $order = app(AdminOrderService::class)->createOrder(
            [['product_variant_id' => $variant->id, 'quantity' => 1]],
            [
                'customer_name' => 'GST Customer',
                'customer_phone' => '9876543210',
                'delivery_address' => 'Bodinayakanur',
            ],
            ['delivery_fee' => 20],
        )->load('items');

        $item = $order->items->first();

        $this->assertSame('18.00', $item->gst_rate);
        $this->assertSame('18.00', $item->gst_amount);
        $this->assertSame(100.0, $item->taxable_amount);
        $this->assertSame('18.00', $order->gst_total);
        $this->assertSame('138.00', $order->total);

        $invoice = view('pdf.invoice', compact('order'))->render();

        $this->assertStringContainsString('GST Included', $invoice);
        $this->assertStringContainsString('18%', $invoice);
        $this->assertStringContainsString('Rs. 18.00', $invoice);
        $this->assertStringContainsString('Rs. 100.00', $invoice);
    }

    public function test_product_without_gst_keeps_zero_tax(): void
    {
        $this->assertSame(0.0, \App\Models\OrderItem::gstIncludedIn(500, 0));
    }
}
