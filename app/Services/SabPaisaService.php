<?php

namespace App\Services;

/**
 * Seam for the planned SabPaisa payment gateway integration.
 *
 * Not wired up yet — checkout currently runs on the manual UPI-QR flow
 * (see CheckoutForm::placeOrder()). Once SabPaisa API credentials are
 * issued, implement initiatePayment()/verifyPayment() here and flip
 * config('payments.gateway') to 'sabpaisa' — that's the only switch
 * the checkout form needs to start using it.
 */
class SabPaisaService
{
    public function isConfigured(): bool
    {
        return filled(config('services.sabpaisa.client_code'))
            && filled(config('services.sabpaisa.auth_key'));
    }

    // TODO: implement once SabPaisa API access is available.
    // public function initiatePayment(Order $order): string { ... } // returns redirect URL
    // public function verifyPayment(array $callbackPayload): bool { ... }
}
