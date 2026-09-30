<?php

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

class NotificationService
{
    public function list(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->notifications()->latest()->paginate($perPage);
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markAsRead(User $user, string $id): DatabaseNotification
    {
        /** @var DatabaseNotification $notification */
        $notification = $user->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        return $notification->refresh();
    }

    public function markAllRead(User $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function send(User $user, object $notification): void
    {
        $user->notify($notification);
    }
}
