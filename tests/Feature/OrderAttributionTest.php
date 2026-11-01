<?php

namespace Tests\Feature;

use App\Http\Middleware\CaptureOrderAttribution;
use App\Livewire\Storefront\CheckoutForm;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\OrderAttributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Tests\TestCase;

class OrderAttributionTest extends TestCase
{
    use RefreshDatabase;

    private OrderAttributionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OrderAttributionService::class);
    }

    public function test_direct_traffic_is_attributed_as_direct(): void
    {
        $request = Request::create('http://127.0.0.1:8000/', 'GET');

        $touch = $this->service->parseRequest($request);

        $this->assertSame('Direct', $touch['origin']);
        $this->assertSame('direct', $touch['source_type']);
        $this->assertSame('direct', $touch['source']);
        $this->assertSame('none', $touch['medium']);
    }

    public function test_organic_google_search_traffic_is_attributed_correctly(): void
    {
        $request = Request::create('http://127.0.0.1:8000/products', 'GET', [], [], [], [
            'HTTP_REFERER' => 'https://www.google.co.in/',
        ]);

        $touch = $this->service->parseRequest($request);

        $this->assertSame('Organic: Google', $touch['origin']);
        $this->assertSame('organic', $touch['source_type']);
        $this->assertSame('google', $touch['source']);
        $this->assertSame('organic', $touch['medium']);
    }

    public function test_paid_google_ads_with_gclid_is_attributed_as_paid_google(): void
    {
        $request = Request::create('http://127.0.0.1:8000/products/mango?gclid=test_gclid_123', 'GET');

        $touch = $this->service->parseRequest($request);

        $this->assertSame('Paid: Google', $touch['origin']);
        $this->assertSame('paid', $touch['source_type']);
        $this->assertSame('google', $touch['source']);
        $this->assertSame('cpc', $touch['medium']);
        $this->assertSame('test_gclid_123', $touch['click_id']);
    }

    public function test_paid_facebook_ads_with_fbclid_is_attributed_as_paid_facebook(): void
    {
        $request = Request::create('http://127.0.0.1:8000/?fbclid=test_fbclid_456', 'GET');

        $touch = $this->service->parseRequest($request);

        $this->assertSame('Paid: Facebook', $touch['origin']);
        $this->assertSame('paid', $touch['source_type']);
        $this->assertSame('facebook', $touch['source']);
        $this->assertSame('cpc', $touch['medium']);
        $this->assertSame('test_fbclid_456', $touch['click_id']);
    }

    public function test_instagram_referrer_is_attributed_as_source_instagram(): void
    {
        $request = Request::create('http://127.0.0.1:8000/products', 'GET', [], [], [], [
            'HTTP_REFERER' => 'https://l.instagram.com/',
        ]);

        $touch = $this->service->parseRequest($request);

        $this->assertSame('Source: Instagram', $touch['origin']);
        $this->assertSame('social', $touch['source_type']);
        $this->assertSame('instagram', $touch['source']);
    }

    public function test_whatsapp_referrer_is_attributed_as_source_whatsapp(): void
    {
        $request = Request::create('http://127.0.0.1:8000/cart', 'GET', [], [], [], [
            'HTTP_REFERER' => 'https://web.whatsapp.com/',
        ]);

        $touch = $this->service->parseRequest($request);

        $this->assertSame('Source: WhatsApp', $touch['origin']);
        $this->assertSame('social', $touch['source_type']);
        $this->assertSame('whatsapp', $touch['source']);
    }

    public function test_youtube_referrer_is_attributed_as_source_youtube(): void
    {
        $request = Request::create('http://127.0.0.1:8000/', 'GET', [], [], [], [
            'HTTP_REFERER' => 'https://www.youtube.com/',
        ]);

        $touch = $this->service->parseRequest($request);

        $this->assertSame('Source: YouTube', $touch['origin']);
        $this->assertSame('social', $touch['source_type']);
        $this->assertSame('youtube', $touch['source']);
    }

    public function test_external_blog_referrer_is_attributed_as_referral(): void
    {
        $request = Request::create('http://127.0.0.1:8000/', 'GET', [], [], [], [
            'HTTP_REFERER' => 'https://freshfoodguide.in/best-mangoes',
        ]);

        $touch = $this->service->parseRequest($request);

        $this->assertSame('Referral: freshfoodguide.in', $touch['origin']);
        $this->assertSame('referral', $touch['source_type']);
        $this->assertSame('freshfoodguide.in', $touch['source']);
        $this->assertSame('referral', $touch['medium']);
    }

    public function test_utm_campaign_and_cpc_medium_attribute_as_paid_source(): void
    {
        $request = Request::create('http://127.0.0.1:8000/?utm_source=facebook&utm_medium=cpc&utm_campaign=diwali_fest&utm_term=mangoes&utm_content=banner1', 'GET');

        $touch = $this->service->parseRequest($request);

        $this->assertSame('Paid: Facebook', $touch['origin']);
        $this->assertSame('paid', $touch['source_type']);
        $this->assertSame('facebook', $touch['source']);
        $this->assertSame('cpc', $touch['medium']);
        $this->assertSame('diwali_fest', $touch['campaign']);
        $this->assertSame('mangoes', $touch['term']);
        $this->assertSame('banner1', $touch['content']);
    }

    public function test_device_type_detection(): void
    {
        $mobileReq = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1',
        ]);
        $tabletReq = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPad; CPU OS 16_0 like Mac OS X) AppleWebKit/605.1.15 Safari/604.1',
        ]);
        $desktopReq = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
        ]);

        $this->assertSame('Mobile', $this->service->parseRequest($mobileReq)['device_type']);
        $this->assertSame('Tablet', $this->service->parseRequest($tabletReq)['device_type']);
        $this->assertSame('Desktop', $this->service->parseRequest($desktopReq)['device_type']);
    }

    public function test_first_touch_and_last_touch_are_preserved(): void
    {
        // First visit from Organic Google
        $firstReq = Request::create('http://127.0.0.1:8000/', 'GET', [], [], [], [
            'HTTP_REFERER' => 'https://www.google.com/',
        ]);
        $res1 = $this->service->capture($firstReq);

        $this->assertSame('Organic: Google', $res1['first_touch']['origin']);
        $this->assertSame('Organic: Google', $res1['last_touch']['origin']);

        // Second visit from Facebook Ads
        $secondReq = Request::create('http://127.0.0.1:8000/?utm_source=facebook&utm_medium=cpc&utm_campaign=festive', 'GET');
        $res2 = $this->service->capture($secondReq);

        $this->assertSame('Organic: Google', $res2['first_touch']['origin']);
        $this->assertSame('Paid: Facebook', $res2['last_touch']['origin']);

        $attribution = $this->service->getOrderAttributionData($secondReq);
        $this->assertSame('Paid: Facebook', $attribution['attribution_origin']);
        $this->assertSame('Organic: Google', $attribution['attribution_data']['first_touch']['origin']);
        $this->assertSame('Paid: Facebook', $attribution['attribution_data']['last_touch']['origin']);
        $this->assertSame('festive', $attribution['attribution_campaign']);
    }

    public function test_storefront_order_placement_persists_attribution(): void
    {
        $category = Category::create(['name' => 'Mangoes', 'slug' => 'mangoes', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Imam Pasand',
            'slug' => 'imam-pasand',
            'base_price' => 250,
            'unit' => 'kg',
            'is_active' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => '3 kg',
            'sku' => 'IP-3KG',
            'price' => 750,
            'weight_value' => 3,
            'weight_unit' => 'kg',
            'stock_qty' => 10,
            'is_active' => true,
        ]);
        DeliveryZone::create([
            'name' => 'Tamil Nadu',
            'match_type' => 'state',
            'match_values' => ['Tamil Nadu'],
            'rate_per_kg' => 10,
            'eta_days' => 2,
            'is_active' => true,
        ]);

        app(CartService::class)->add($variant->id, 1);

        // Simulate visitor coming via Instagram Ad
        session([
            'order_attribution_first' => [
                'origin' => 'Organic: Google',
                'source_type' => 'organic',
                'source' => 'google',
                'medium' => 'organic',
                'campaign' => null,
                'timestamp' => now()->subDays(2)->toIso8601String(),
            ],
            'order_attribution_last' => [
                'origin' => 'Paid: Instagram',
                'source_type' => 'paid',
                'source' => 'instagram',
                'medium' => 'cpc',
                'campaign' => 'summer_sweetness',
                'term' => 'fresh_mango',
                'content' => 'story_ad_1',
                'click_id' => 'ig_click_999',
                'device_type' => 'Mobile',
                'referrer_url' => 'https://l.instagram.com/',
                'landing_url' => 'http://127.0.0.1:8000/products/imam-pasand',
                'user_agent' => 'Mozilla/5.0 (iPhone)',
                'timestamp' => now()->toIso8601String(),
            ],
        ]);

        Livewire::test(CheckoutForm::class)
            ->set('customer_name', 'Sundar Pichai')
            ->set('customer_phone', '9876543210')
            ->set('customer_email', 'sundar@example.com')
            ->set('delivery_address', '10 Garden Road')
            ->set('postcode', '625513')
            ->set('city', 'Theni')
            ->set('state', 'Tamil Nadu')
            ->call('placeOrder');

        $order = Order::where('customer_phone', '9876543210')->latest()->first();
        $this->assertNotNull($order);
        $this->assertSame('Paid: Instagram', $order->attribution_origin);
        $this->assertSame('paid', $order->attribution_source_type);
        $this->assertSame('instagram', $order->attribution_source);
        $this->assertSame('cpc', $order->attribution_medium);
        $this->assertSame('summer_sweetness', $order->attribution_campaign);
        $this->assertSame('ig_click_999', $order->attribution_click_id);
        $this->assertSame('Mobile', $order->attribution_device_type);
        $this->assertSame('https://l.instagram.com/', $order->attribution_referrer_url);

        // Verify first-touch and last-touch in JSON data
        $this->assertSame('Organic: Google', $order->firstTouch()['origin']);
        $this->assertSame('Paid: Instagram', $order->lastTouch()['origin']);
        $this->assertStringContainsString('Organic: Google', $order->firstTouchSummary());
        $this->assertStringContainsString('Paid: Instagram', $order->lastTouchSummary());
        $this->assertStringContainsString('summer_sweetness', $order->lastTouchSummary());
    }

    public function test_admin_orders_page_renders_with_origin_column(): void
    {
        $this->seed(\Database\Seeders\RolesAndAdminSeeder::class);
        $admin = \App\Models\User::where('email', 'admin@merza.com')->firstOrFail();

        $order = Order::create([
            'order_number' => 'MRZ-ATTR1',
            'customer_name' => 'Attribution Customer',
            'customer_phone' => '9876543210',
            'delivery_address' => '12 Market St',
            'subtotal' => 500,
            'total' => 500,
            'payment_method' => 'upi',
            'attribution_origin' => 'Paid: Facebook',
            'attribution_source_type' => 'paid',
        ]);

        $response = $this->actingAs($admin)->get('/admin/orders');
        $response->assertOk();
        $response->assertSee('Origin');
        $response->assertSee('Paid: Facebook');
    }
}
