<?php

namespace App\Notifications\Courses;

use App\Models\Course;
use App\Notifications\Concerns\HasTranslatedBroadcast;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CoursePublishedNotification extends Notification implements ShouldQueue
{
    use HasTranslatedBroadcast, Queueable;

    public function __construct(
        public readonly Course $course,
        public readonly int $userId,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'course_published',
            'key' => 'course_published',
            'params' => ['course' => $this->courseTitle()],
            'course_id' => $this->course->getKey(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->broadcastPayload($notifiable, $this->toArray($notifiable)));
    }

    public function broadcastAs(): string
    {
        return 'course_published';
    }

    public function broadcastType(): string
    {
        return 'course_published';
    }

    public function broadcastOn(): array
    {
        // PrivateChannel adds the `private-` prefix itself.
        return [new PrivateChannel('user.'.$this->userId)];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim((string) config('app.frontend_url', config('app.url')), '/')
            .'/courses/'.$this->course->getKey();

        return (new MailMessage)
            ->subject(__('messages.notif.course_published_title', ['course' => $this->courseTitle()]))
            ->line(__('messages.notif.course_published_body', ['course' => $this->courseTitle()]))
            ->action($this->courseTitle(), $url);
    }

    private function courseTitle(): string
    {
        return (string) ($this->course->translation()?->title ?? '#'.$this->course->getKey());
    }
}
