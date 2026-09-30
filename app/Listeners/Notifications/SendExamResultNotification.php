<?php

namespace App\Listeners\Notifications;

use App\Events\Exams\ExamAttemptGraded;
use App\Notifications\Exams\ExamResultNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendExamResultNotification implements ShouldQueue
{
    public function handle(ExamAttemptGraded $event): void
    {
        $attempt = $event->attempt;
        $user = $attempt->user;

        $notification = new ExamResultNotification($attempt, $user->getKey());
        $notification->locale = $user->preferredLocale();

        $user->notify($notification);
    }
}
