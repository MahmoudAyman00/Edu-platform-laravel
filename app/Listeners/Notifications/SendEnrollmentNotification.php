<?php

namespace App\Listeners\Notifications;

use App\Events\Enrollments\StudentEnrolled;
use App\Notifications\Enrollments\EnrolledInCourseNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendEnrollmentNotification implements ShouldQueue
{
    public function handle(StudentEnrolled $event): void
    {
        $enrollment = $event->enrollment;
        $user = $enrollment->user;

        $notification = new EnrolledInCourseNotification($enrollment->course, $user->getKey());
        $notification->locale = $user->preferredLocale();

        $user->notify($notification);
    }
}
