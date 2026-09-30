<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;

class PaymentCallbackController extends Controller
{
    public function __construct(protected PaymentGatewayService $payments) {}

    public function __invoke(Request $request, PaymentTransaction $transaction)
    {
        abort_unless($this->payments->isAvailable($transaction->method), 404);

        try {
            $transaction = $this->payments->handleCallback($transaction, $request);
        } catch (\Throwable $e) {
            report($e);
            $transaction->refresh();
        }

        $order = $transaction->order;
        $query = ['invoice' => $order?->invoice_no];

        return redirect()->route('website.track', array_filter($query))->with(
            $transaction->status === PaymentTransaction::PAID ? 'success' : 'error',
            $transaction->status === PaymentTransaction::PAID
                ? 'Payment received. Thank you!'
                : 'Payment was not completed. Your order is saved; you can pay on delivery or contact us.'
        );
    }
}
