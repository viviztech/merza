<?php

namespace Tests\Feature;

use App\Livewire\Storefront\CheckoutForm;
use App\Models\Order;
use App\Services\RazorpayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class RazorpayCheckoutRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private function pendingOrder(): Order
    {
        $order = Order::create([
            'customer_name' => 'Checkout Customer',
            'customer_phone' => '9876543210',
            'delivery_address' => 'Test Road',
            'subtotal' => 500,
            'total' => 500,
            'payment_method' => 'upi',
            'razorpay_order_id' => 'order_pending_test',
        ]);
        $order->items()->create([
            'product_name' => 'Fruit', 'variant_name' => 'Box',
            'quantity' => 1, 'unit_price' => 500, 'subtotal' => 500,
        ]);

        return $order;
    }

    public function test_reload_and_cancel_preserve_pending_order_and_items(): void
    {
        Queue::fake();
        $order = $this->pendingOrder();
        session(['pending_razorpay_order_id' => $order->id]);

        Livewire::test(CheckoutForm::class)
            ->assertSet('orderId', $order->id)
            ->assertSet('razorpayPaymentPending', true)
            ->call('cancelRazorpayPayment')
            ->assertSet('razorpayPaymentPending', true)
            ->assertSee('Check payment status');

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id]);
        $this->assertSame($order->id, session('pending_razorpay_order_id'));
    }

    public function test_pending_payment_blocks_a_second_order(): void
    {
        Queue::fake();
        $order = $this->pendingOrder();
        session(['pending_razorpay_order_id' => $order->id]);

        Livewire::test(CheckoutForm::class)->call('placeOrder');

        $this->assertSame(1, Order::count());
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id]);
    }

    public function test_guest_cannot_set_the_order_id_to_target_another_order(): void
    {
        Queue::fake();
        $order = $this->pendingOrder();
        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::test(CheckoutForm::class)->set('orderId', $order->id);
    }

    public function test_checkout_can_be_reopened_only_when_no_options_were_sent(): void
    {
        Queue::fake();
        $order = $this->pendingOrder();
        config(['payments.gateway' => 'razorpay']);
        session(['pending_razorpay_order_id' => $order->id, 'razorpay_checkout_started' => false]);
        $service = Mockery::mock(RazorpayService::class);
        $service->shouldReceive('isConfigured')->andReturnTrue();
        $service->shouldReceive('checkoutOptions')->once()
            ->with(Mockery::on(fn (Order $candidate) => $candidate->is($order)))
            ->andReturn(['order_id' => 'order_pending_test']);
        $this->app->instance(RazorpayService::class, $service);

        Livewire::test(CheckoutForm::class)
            ->assertSee('Open payment window')
            ->call('retryRazorpayPayment')
            ->assertDispatched('razorpay-checkout');

        $this->assertTrue(session('razorpay_checkout_started'));
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }
}
