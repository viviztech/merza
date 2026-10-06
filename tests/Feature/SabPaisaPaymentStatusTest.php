<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SabPaisaPaymentStatusTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        config()->set('services.sabpaisa.api_key', 'test-api-key');
        config()->set('services.sabpaisa.secret_key', 'test-secret-key');
        config()->set('services.sabpaisa.webhook_secret', 'test-webhook-secret');
        config()->set('services.sabpaisa.merchant_id', 'test-merchant');

        $this->order = Order::create([
            'customer_name' => 'Payment Customer',
            'customer_phone' => '9876543210',
            'delivery_address' => '12 Market Road',
            'subtotal' => 500,
            'total' => 500,
            'payment_method' => 'upi',
        ]);
    }

    public function test_valid_millisecond_webhook_marks_matching_order_paid(): void
    {
        $this->sendWebhook(['paid_amount' => 500.00])
            ->assertOk();

        $this->assertSame('paid', $this->order->fresh()->payment_status);
        $this->assertSame('SP-123', $this->order->fresh()->payment_reference);
    }

    public function test_webhook_with_wrong_amount_does_not_mark_order_paid(): void
    {
        $this->sendWebhook(['paid_amount' => 100.00])
            ->assertStatus(422);

        $this->assertSame('unpaid', $this->order->fresh()->payment_status);
    }

    public function test_old_webhook_signature_is_rejected(): void
    {
        $this->sendWebhook([], (int) round(microtime(true) * 1000) - 360000)
            ->assertStatus(400);

        $this->assertSame('unpaid', $this->order->fresh()->payment_status);
    }

    public function test_signed_return_uses_matching_enquiry_to_mark_order_paid(): void
    {
        Http::fake(['*/api/v2/payments/enquiry' => Http::response($this->enquiryResult())]);

        $this->get($this->signedReturnUrl())->assertRedirect();

        $this->assertSame('paid', $this->order->fresh()->payment_status);
        $this->assertSame('SP-123', $this->order->fresh()->payment_reference);
    }

    public function test_signed_success_return_does_not_show_success_for_unpaid_order(): void
    {
        Http::fake(['*/api/v2/payments/enquiry' => Http::response($this->enquiryResult(['paidAmount' => 100]))]);

        $response = $this->get($this->signedReturnUrl());
        $response->assertRedirect();

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('Confirming Payment')
            ->assertDontSee('Payment Successful!');

        $this->assertSame('unpaid', $this->order->fresh()->payment_status);
    }

    private function sendWebhook(array $changes = [], ?int $timestamp = null): \Illuminate\Testing\TestResponse
    {
        $payload = array_merge([
            'event' => 'payment.success',
            'txn_id' => 'SP-123',
            'merchant_txn_id' => $this->order->order_number,
            'status' => 'SUCCESS',
            'request_amount' => 500.00,
            'paid_amount' => 500.00,
            'currency' => 'INR',
        ], $changes);
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp ??= (int) round(microtime(true) * 1000);
        $signature = base64_encode(hash_hmac('sha256', $timestamp.'.'.$body, 'test-webhook-secret', true));

        return $this->call('POST', route('webhook.sabpaisa.handle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SABPAISA_SIGNATURE' => $timestamp.'.'.$signature,
        ], $body);
    }

    private function enquiryResult(array $changes = []): array
    {
        return array_merge([
            'success' => true,
            'status' => 'SUCCESS',
            'merchantTxnId' => $this->order->order_number,
            'amountPaise' => 50000,
            'paidAmount' => 500.00,
            'currency' => 'INR',
            'txnId' => 'SP-123',
        ], $changes);
    }

    private function signedReturnUrl(): string
    {
        $params = [
            'transaction_id' => 'SP-123',
            'merchant_txn_id' => $this->order->order_number,
            'status' => 'SUCCESS',
            'amount' => '500.00',
            'paid_amount' => '500.00',
            'payment_mode' => 'UPI',
            'timestamp' => (string) round(microtime(true) * 1000),
        ];
        ksort($params);
        $data = collect($params)->map(fn ($value, $key) => "{$key}={$value}")->implode('|');
        $params['signature'] = hash_hmac('sha256', $data, 'test-secret-key');

        return route('payment.return').'?'.http_build_query($params);
    }
}
