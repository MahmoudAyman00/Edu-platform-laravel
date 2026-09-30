<?php

namespace App\Listeners\Notifications;

use App\Events\Payments\PaymentExpired;
use App\Notifications\Payments\PaymentExpiredNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPaymentExpiredNotification implements ShouldQueue
{
    public function handle(PaymentExpired $event): void
    {
        $payment = $event->payment;
        $user = $payment->user;

        $notification = new PaymentExpiredNotification($payment, $user->getKey());
        $notification->locale = $user->preferredLocale();

        $user->notify($notification);
    }
}
