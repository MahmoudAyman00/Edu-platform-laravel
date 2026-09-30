<?php

namespace App\Events\Payments;

use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;

class PaymentFailed
{
    use Dispatchable;

    public function __construct(public readonly Payment $payment)
    {
    }
}
