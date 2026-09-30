<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;
use App\Notifications\Courses\CoursePublishedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendCoursePublishedNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $courseId)
    {
    }

    public function handle(): void
    {
        $course = Course::with('translations')->findOrFail($this->courseId);

        // Course announcements go to students only (admins are excluded).
        User::query()->where('role', UserRole::STUDENT)->chunkById(200, function ($users) use ($course) {
            foreach ($users as $user) {
                $notification = new CoursePublishedNotification($course, $user->getKey());
                $notification->locale = $user->preferredLocale();
                $user->notify($notification);
            }
        });
    }
}
