<?php

/*
|--------------------------------------------------------------------------
| Online payment methods
|--------------------------------------------------------------------------
|
| `offline` methods (COD, confirmation charge) are switched on/off in
| CMS → Delivery settings. `gateway` methods stay unavailable until BOTH
| every credential below is set in .env AND a driver class implementing
| App\Contracts\PaymentGateway is configured. Nothing here is activated
| without credentials.
|
*/

return [

    'methods' => [

        'cash_on_delivery' => [
            'type' => 'offline',
            'label' => 'Cash on delivery',
            'description' => 'Pay the full amount when your order arrives.',
        ],

        'confirmation_charge' => [
            'type' => 'offline',
            'label' => 'Confirmation charge',
            'description' => 'Pay a small advance now; the balance is due on delivery.',
        ],

        'bkash' => [
            'type' => 'gateway',
            'label' => 'bKash',
            'description' => 'Pay securely with your bKash account.',
            'driver' => env('BKASH_DRIVER'),
            'credentials' => [
                'app_key' => env('BKASH_APP_KEY'),
                'app_secret' => env('BKASH_APP_SECRET'),
                'username' => env('BKASH_USERNAME'),
                'password' => env('BKASH_PASSWORD'),
            ],
            'sandbox' => (bool) env('BKASH_SANDBOX', true),
        ],

        'nagad' => [
            'type' => 'gateway',
            'label' => 'Nagad',
            'description' => 'Pay securely with your Nagad account.',
            'driver' => env('NAGAD_DRIVER'),
            'credentials' => [
                'merchant_id' => env('NAGAD_MERCHANT_ID'),
                'merchant_public_key' => env('NAGAD_PUBLIC_KEY'),
                'merchant_private_key' => env('NAGAD_PRIVATE_KEY'),
            ],
            'sandbox' => (bool) env('NAGAD_SANDBOX', true),
        ],

        'online_gateway' => [
            'type' => 'gateway',
            'label' => 'Card / online banking',
            'description' => 'Pay by card or online banking.',
            'driver' => env('ONLINE_GATEWAY_DRIVER'),
            'credentials' => [
                'store_id' => env('ONLINE_GATEWAY_STORE_ID'),
                'store_password' => env('ONLINE_GATEWAY_STORE_PASSWORD'),
            ],
            'sandbox' => (bool) env('ONLINE_GATEWAY_SANDBOX', true),
        ],

    ],

];
