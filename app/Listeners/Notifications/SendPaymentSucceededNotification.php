<?php

namespace App\Listeners\Notifications;

use App\Events\Payments\PaymentSucceeded;
use App\Notifications\Payments\PaymentSucceededNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPaymentSucceededNotification implements ShouldQueue
{
    public function handle(PaymentSucceeded $event): void
    {
        $payment = $event->payment;
        $user = $payment->user;

        $notification = new PaymentSucceededNotification($payment, $user->getKey());
        $notification->locale = $user->preferredLocale();

        $user->notify($notification);
    }
}
