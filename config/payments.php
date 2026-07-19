<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Active Payment Gateway
    |--------------------------------------------------------------------------
    |
    | 'manual' = today's UPI-QR + self-reported transaction ID/screenshot flow.
    | 'sabpaisa' = once SabPaisa API credentials are live, flip this and the
    | checkout payment section switches to that gateway. See SabPaisaService.
    |
    */

    'gateway' => env('PAYMENT_GATEWAY', 'manual'),

];
