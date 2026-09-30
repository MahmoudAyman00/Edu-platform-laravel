<?php

namespace App\Listeners\Enrollments;

use App\Events\Payments\PaymentSucceeded;
use App\Services\Payments\PaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;

class EnrollStudentAfterPayment implements ShouldQueue
{
    public function __construct(protected PaymentService $payments)
    {
    }

    public function handle(PaymentSucceeded $event): void
    {
        $this->payments->linkEnrollment($event->payment);
    }
}
