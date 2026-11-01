<?php

namespace App\Jobs;

use App\Livewire\Storefront\CheckoutForm;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendRazorpayOrderConfirmation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $orderId) {}

    public function handle(): void
    {
        $order = Order::find($this->orderId);

        if ($order && $order->payment_status === 'paid') {
            CheckoutForm::sendOrderConfirmationWhatsApp($order);
        }
    }
}
