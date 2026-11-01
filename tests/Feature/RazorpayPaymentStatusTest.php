<?php

namespace Tests\Feature;

use App\Jobs\SendOrderWebhookJob;
use App\Jobs\SendRazorpayOrderConfirmation;
use App\Models\Order;
use App\Services\RazorpayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class RazorpayPaymentStatusTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        config()->set('services.razorpay.key_id', 'rzp_test_example');
        config()->set('services.razorpay.key_secret', 'test-secret');
        config()->set('services.razorpay.webhook_secret', 'test-webhook-secret');

        $this->order = Order::create([
            'customer_name' => 'Razorpay Customer',
            'customer_phone' => '9876543210',
            'delivery_address' => '12 Market Road',
            'subtotal' => 500,
            'total' => 500,
            'payment_method' => 'upi',
            'razorpay_order_id' => 'order_test_123',
        ]);
    }

    public function test_captured_webhook_marks_only_the_matching_order_paid(): void
    {
        $this->sendWebhook('payment.captured')->assertOk();

        $order = $this->order->fresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('pay_test_123', $order->payment_reference);
        $this->assertSame('pay_test_123', $order->razorpay_payment_id);
        $this->assertSame('captured', $order->razorpay_payment_status);
        $this->assertNotNull($order->payment_verified_at);
    }

    public function test_webhook_with_wrong_amount_does_not_mark_order_paid(): void
    {
        $this->sendWebhook('payment.captured', ['amount' => 10000])
            ->assertStatus(422);

        $this->assertSame('unpaid', $this->order->fresh()->payment_status);
    }

    public function test_invalid_webhook_signature_is_rejected(): void
    {
        $payload = json_encode($this->payload('payment.captured'), JSON_THROW_ON_ERROR);

        $this->call('POST', route('webhook.razorpay.handle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => 'not-a-valid-signature',
        ], $payload)->assertStatus(400);

        $this->assertSame('unpaid', $this->order->fresh()->payment_status);
    }

    public function test_duplicate_captured_webhook_is_idempotent(): void
    {
        $this->sendWebhook('payment.captured')->assertOk();
        $this->sendWebhook('payment.captured')->assertOk();

        $this->assertSame('paid', $this->order->fresh()->payment_status);
        $this->assertSame(1, Order::where('razorpay_payment_id', 'pay_test_123')->count());
        $this->assertCount(1, Queue::pushed(SendOrderWebhookJob::class)
            ->filter(fn ($job) => $job->orderId === $this->order->id && $job->event === 'order.created'));
        Queue::assertPushed(SendRazorpayOrderConfirmation::class, 1);
    }

    public function test_failed_payment_stays_unpaid_and_can_be_retried(): void
    {
        $this->sendWebhook('payment.failed', ['status' => 'failed'])
            ->assertOk();

        $order = $this->order->fresh();
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame('failed', $order->razorpay_payment_status);
    }

    public function test_checkout_verification_endpoint_discards_browser_amount(): void
    {
        $razorpay = Mockery::mock(RazorpayService::class);
        $razorpay->shouldReceive('verifyCheckoutPayment')
            ->once()
            ->with(Mockery::on(fn (array $attributes) => ! array_key_exists('amount', $attributes)
                && $attributes['razorpay_order_id'] === 'order_test_123'
                && $attributes['razorpay_payment_id'] === 'pay_test_123'))
            ->andReturn($this->order);
        $this->app->instance(RazorpayService::class, $razorpay);

        $this->withSession(['pending_razorpay_order_id' => $this->order->id])
            ->postJson(route('payment.razorpay.verify'), [
            'razorpay_order_id' => 'order_test_123',
            'razorpay_payment_id' => 'pay_test_123',
            'razorpay_signature' => 'validly-verified-by-service',
            'amount' => 1,
        ])->assertOk()
            ->assertJsonPath('status', 'PENDING')
            ->assertJsonStructure(['redirect_url']);
    }

    public function test_successful_checkout_verification_can_be_retried_without_duplicate_order_created(): void
    {
        $this->order->update([
            'payment_status' => 'paid',
            'razorpay_payment_id' => 'pay_test_123',
        ]);
        $razorpay = Mockery::mock(RazorpayService::class);
        $razorpay->shouldReceive('verifyCheckoutPayment')->twice()->andReturn($this->order->fresh());
        $this->app->instance(RazorpayService::class, $razorpay);
        $payload = [
            'razorpay_order_id' => 'order_test_123',
            'razorpay_payment_id' => 'pay_test_123',
            'razorpay_signature' => 'validly-verified-by-service',
        ];

        $this->withSession(['pending_razorpay_order_id' => $this->order->id])
            ->postJson(route('payment.razorpay.verify'), $payload)
            ->assertOk()->assertJsonPath('status', 'SUCCESS')
            ->assertSessionHas('completed_razorpay_order_id', $this->order->id);
        $this->postJson(route('payment.razorpay.verify'), $payload)
            ->assertOk()->assertJsonPath('status', 'SUCCESS');

        $this->assertCount(0, Queue::pushed(SendOrderWebhookJob::class)
            ->filter(fn ($job) => $job->event === 'order.created'));
        Queue::assertNotPushed(SendRazorpayOrderConfirmation::class);
    }

    public function test_checkout_verification_rejects_another_sessions_order(): void
    {
        $other = Order::create([
            'customer_name' => 'Other Customer',
            'customer_phone' => '9123456780',
            'delivery_address' => 'Other address',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'upi',
            'razorpay_order_id' => 'order_other',
        ]);

        $this->withSession(['pending_razorpay_order_id' => $other->id])
            ->postJson(route('payment.razorpay.verify'), [
                'razorpay_order_id' => 'order_test_123',
                'razorpay_payment_id' => 'pay_test_123',
                'razorpay_signature' => 'valid-signature',
            ])->assertForbidden();

        $this->assertSame('unpaid', $this->order->fresh()->payment_status);
    }

    public function test_checkout_options_include_the_project_name_and_logo(): void
    {
        config()->set('app.name', 'Merza CRM');

        $options = app(RazorpayService::class)->checkoutOptions($this->order);

        $this->assertSame('Merza CRM', $options['name']);
        $this->assertSame(asset('images/logo.png'), $options['image']);
        $this->assertSame('rzp_test_example', $options['key']);
        $this->assertSame('order_test_123', $options['order_id']);
    }

    public function test_checkout_verification_requires_valid_attributes(): void
    {
        $this->postJson(route('payment.razorpay.verify'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature']);
    }

    public function test_checkout_verification_fails_when_order_does_not_exist(): void
    {
        $this->postJson(route('payment.razorpay.verify'), [
            'razorpay_order_id' => 'order_nonexistent',
            'razorpay_payment_id' => 'pay_nonexistent',
            'razorpay_signature' => 'some_signature',
        ])->assertStatus(403)
            ->assertJsonPath('message', 'This payment does not match your checkout session.');
    }

    public function test_checkout_confirmation_displays_success_when_paid(): void
    {
        $this->order->update([
            'payment_status' => 'paid',
            'razorpay_payment_id' => 'pay_test_123',
            'razorpay_payment_status' => 'captured',
            'payment_verified_at' => now(),
        ]);

        $url = \Illuminate\Support\Facades\URL::signedRoute('payment.razorpay.confirmation', ['order' => $this->order->id]);

        $response = $this->get($url);
        $response->assertOk();
        $response->assertSee('Payment Successful!');
        $response->assertSee($this->order->order_number);
    }

    public function test_webhook_completion_clears_only_the_matching_checkout_session_on_confirmation(): void
    {
        $this->sendWebhook('payment.captured')->assertOk();
        $url = \Illuminate\Support\Facades\URL::signedRoute('payment.razorpay.confirmation', ['order' => $this->order->id]);

        $this->withSession(['pending_razorpay_order_id' => $this->order->id])
            ->get($url)
            ->assertOk()
            ->assertSessionMissing('pending_razorpay_order_id')
            ->assertSessionHas('completed_razorpay_order_id', $this->order->id);
    }

    public function test_checkout_confirmation_displays_failed_when_payment_failed(): void
    {
        $this->order->update([
            'payment_status' => 'unpaid',
            'razorpay_payment_status' => 'failed',
        ]);

        $url = \Illuminate\Support\Facades\URL::signedRoute('payment.razorpay.confirmation', ['order' => $this->order->id]);

        $response = $this->get($url);
        $response->assertOk();
        $response->assertSee('Payment Failed');
        $response->assertSee($this->order->order_number);
    }

    public function test_checkout_form_dispatches_razorpay_event_and_creates_order(): void
    {
        config()->set('payments.gateway', 'razorpay');

        $category = \App\Models\Category::create(['name' => 'Mangoes', 'slug' => 'mangoes', 'is_active' => true]);
        $product = \App\Models\Product::create([
            'category_id' => $category->id,
            'name' => 'Banganapalli',
            'slug' => 'banganapalli',
            'base_price' => 200,
            'unit' => 'kg',
            'is_active' => true,
        ]);
        $variant = \App\Models\ProductVariant::create([
            'product_id' => $product->id,
            'name' => '3 kg',
            'sku' => 'BNG-3KG',
            'price' => 600,
            'weight_value' => 3,
            'weight_unit' => 'kg',
            'stock_qty' => 10,
            'is_active' => true,
        ]);
        \App\Models\DeliveryZone::create([
            'name' => 'Tamil Nadu',
            'match_type' => 'state',
            'match_values' => ['Tamil Nadu'],
            'rate_per_kg' => 10,
            'eta_days' => 2,
            'is_active' => true,
        ]);

        app(\App\Services\CartService::class)->add($variant->id, 1);

        $mockRazorpay = Mockery::mock(RazorpayService::class);
        $mockRazorpay->shouldReceive('isConfigured')->andReturn(true);
        $mockRazorpay->shouldReceive('checkoutOptions')
            ->once()
            ->andReturn([
                'key' => 'rzp_test_example',
                'order_id' => 'order_mocked_123',
                'name' => 'Merza CRM',
                'image' => asset('images/logo.png'),
                'description' => 'Merza order MRZ-TEST',
                'prefill' => ['name' => 'Jane Customer', 'contact' => '919876543210'],
                'theme' => ['color' => '#D97706'],
            ]);
        $this->app->instance(RazorpayService::class, $mockRazorpay);

        $component = \Livewire\Livewire::test(\App\Livewire\Storefront\CheckoutForm::class)
            ->set('customer_name', 'Jane Customer')
            ->set('customer_phone', '9123456789')
            ->set('customer_email', 'jane@example.com')
            ->set('delivery_address', '10 Garden Street')
            ->set('postcode', '625513')
            ->set('city', 'Theni')
            ->set('state', 'Tamil Nadu')
            ->call('placeOrder')
            ->assertDispatched('razorpay-checkout')
            ->assertSet('razorpayPaymentPending', true)
            ->assertSet('orderPlaced', false);

        // Before cancellation, order existed for Razorpay checkout options
        $this->assertNotNull(Order::where('customer_phone', '9123456789')->first());

        // Cart is kept intact while payment is pending
        $this->assertSame(1, app(\App\Services\CartService::class)->count());

        // When customer cancels the Razorpay modal
        $component->call('cancelRazorpayPayment')
            ->assertSet('razorpayPaymentCancelled', true)
            ->assertSet('orderPlaced', false)
            ->assertSee('Payment status')
            ->assertSee('Your payment may still be processing');

        // The order remains available for a late gateway webhook.
        $this->assertNotNull(Order::where('customer_phone', '9123456789')->first());

        // Cart is still intact so customer can checkout again
        $this->assertSame(1, app(\App\Services\CartService::class)->count());
    }

    private function sendWebhook(string $event, array $paymentChanges = []): TestResponse
    {
        $payload = json_encode($this->payload($event, $paymentChanges), JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $payload, 'test-webhook-secret');

        return $this->call('POST', route('webhook.razorpay.handle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
        ], $payload);
    }

    private function payload(string $event, array $paymentChanges = []): array
    {
        $status = match ($event) {
            'payment.authorized' => 'authorized',
            'payment.failed' => 'failed',
            default => 'captured',
        };

        return [
            'event' => $event,
            'payload' => [
                'payment' => [
                    'entity' => array_merge([
                        'id' => 'pay_test_123',
                        'order_id' => 'order_test_123',
                        'amount' => 50000,
                        'currency' => 'INR',
                        'status' => $status,
                    ], $paymentChanges),
                ],
            ],
        ];
    }
}
