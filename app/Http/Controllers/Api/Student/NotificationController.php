<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Notifications\NotificationResource;
use App\Services\Notifications\NotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function __construct(protected NotificationService $notifications)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->notifications->list(
            $request->user(),
            (int) $request->integer('per_page', 15),
        );

        return $this->paginated(
            $paginator->through(fn ($n) => (new NotificationResource($n))->toArray($request)),
            'messages.notifications.list',
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return $this->success(
            ['unread_count' => $this->notifications->unreadCount($request->user())],
            'messages.notifications.unread_count',
        );
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $this->notifications->markAsRead($request->user(), $id);

        return $this->success(
            (new NotificationResource($notification))->toArray($request),
            'messages.notifications.marked_read',
        );
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $this->notifications->markAllRead($request->user());

        return $this->success(null, 'messages.notifications.all_marked_read');
    }
}
