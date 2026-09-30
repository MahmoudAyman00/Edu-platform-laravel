<?php

namespace App\Listeners\Notifications;

use App\Events\Auth\PasswordResetRequested;
use App\Notifications\Auth\ResetPasswordNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPasswordResetEmail implements ShouldQueue
{
    public function handle(PasswordResetRequested $event): void
    {
        $event->user->notify(new ResetPasswordNotification($event->token));
    }
}
