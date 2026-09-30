<?php

namespace App\Listeners\Notifications;

use App\Events\Courses\CoursePublished;
use App\Jobs\SendCoursePublishedNotificationsJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendCoursePublishedNotifications implements ShouldQueue
{
    public function handle(CoursePublished $event): void
    {
        SendCoursePublishedNotificationsJob::dispatch($event->course->getKey());
    }
}
