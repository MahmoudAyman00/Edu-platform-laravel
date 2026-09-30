<?php

namespace App\Services\Enrollments;

use App\Exceptions\AppException;
use App\Models\Lesson;
use App\Models\User;
use App\Support\CloudStorage;

class PlaybackService
{
    public function __construct(protected EnrollmentService $enrollments)
    {
    }

    /**
     * @return array{play_url: string, expires_in: int}
     */
    public function generatePlaybackUrl(User $user, Lesson $lesson): array
    {
        $course = $lesson->course;

        if (! $this->enrollments->hasAccess($user, $course)) {
            throw AppException::fromKey('messages.enrollments.no_access', 'ENROLLMENT_REQUIRED', 403);
        }

        if (empty($lesson->video_path)) {
            throw AppException::fromKey('messages.enrollments.no_video', 'NO_VIDEO', 400);
        }

        $ttl = (int) config('content.signed_url_ttl', 3600);

        return [
            'play_url' => CloudStorage::presignedDownloadUrl($lesson->video_path, $ttl),
            'expires_in' => $ttl,
        ];
    }
}
