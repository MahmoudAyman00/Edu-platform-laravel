<?php

namespace App\Notifications\Exams;

use App\Models\ExamAttempt;
use App\Notifications\Concerns\HasTranslatedBroadcast;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExamResultNotification extends Notification implements ShouldQueue
{
    use HasTranslatedBroadcast, Queueable;

    public function __construct(
        public readonly ExamAttempt $attempt,
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
        $examTitle = (string) ($this->attempt->exam->translation()?->title ?? '#'.$this->attempt->exam_id);

        return [
            'type' => 'exam_result',
            'key' => 'exam_result',
            'params' => [
                'exam' => $examTitle,
                'score' => $this->attempt->score,
                'status' => $this->attempt->passed ? 'passed' : 'failed',
                'pass_score' => $this->attempt->exam->pass_score,
            ],
            'exam_id' => $this->attempt->exam_id,
            'attempt_id' => $this->attempt->getKey(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->broadcastPayload($notifiable, $this->toArray($notifiable)));
    }

    public function broadcastAs(): string
    {
        return 'exam_result';
    }

    public function broadcastType(): string
    {
        return 'exam_result';
    }

    public function broadcastOn(): array
    {
        // PrivateChannel adds the `private-` prefix itself.
        return [new PrivateChannel('user.'.$this->userId)];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject(__('messages.notif.exam_result_title'))
            ->line(__('messages.notif.exam_result_body', $data['params']));
    }
}
