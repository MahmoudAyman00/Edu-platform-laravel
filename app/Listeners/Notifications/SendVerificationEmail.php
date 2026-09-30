<?php

namespace App\Listeners\Notifications;

use App\Events\Auth\UserRegistered;
use App\Events\Auth\VerificationEmailRequested;
use App\Notifications\Auth\VerifyEmailNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendVerificationEmail implements ShouldQueue
{
    public function handle(UserRegistered|VerificationEmailRequested $event): void
    {
        $event->user->notify(new VerifyEmailNotification($event->user));
    }
}
