<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\Enrollments\PlaybackService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaybackController extends Controller
{
    use ApiResponse;

    public function __construct(protected PlaybackService $playback)
    {
    }

    public function show(Request $request, Lesson $lesson): JsonResponse
    {
        return $this->success(
            $this->playback->generatePlaybackUrl($request->user(), $lesson),
            'messages.playback.url'
        );
    }
}
