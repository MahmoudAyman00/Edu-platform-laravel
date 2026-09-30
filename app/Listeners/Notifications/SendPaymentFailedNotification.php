<?php

namespace App\Listeners\Notifications;

use App\Events\Payments\PaymentFailed;
use App\Notifications\Payments\PaymentFailedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPaymentFailedNotification implements ShouldQueue
{
    public function handle(PaymentFailed $event): void
    {
        $payment = $event->payment;
        $user = $payment->user;

        $notification = new PaymentFailedNotification($payment, $user->getKey());
        $notification->locale = $user->preferredLocale();

        $user->notify($notification);
    }
}
