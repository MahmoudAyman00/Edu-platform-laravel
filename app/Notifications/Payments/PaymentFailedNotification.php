<?php

namespace App\Notifications\Payments;

use App\Models\Payment;
use App\Notifications\Concerns\HasTranslatedBroadcast;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class PaymentFailedNotification extends Notification implements ShouldQueue
{
    use HasTranslatedBroadcast, Queueable;

    public function __construct(
        public readonly Payment $payment,
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
            'type' => 'payment_failed',
            'key' => 'payment_failed',
            'params' => ['course' => $this->courseTitle()],
            'course_id' => $this->payment->course_id,
            'payment_id' => $this->payment->getKey(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->broadcastPayload($notifiable, $this->toArray($notifiable)));
    }

    public function broadcastAs(): string
    {
        return 'payment_failed';
    }

    public function broadcastType(): string
    {
        return 'payment_failed';
    }

    public function broadcastOn(): array
    {
        // PrivateChannel adds the `private-` prefix itself.
        return [new PrivateChannel('user.'.$this->userId)];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('messages.notif.payment_failed_title'))
            ->line(__('messages.notif.payment_failed_body', ['course' => $this->courseTitle()]));
    }

    private function courseTitle(): string
    {
        return (string) ($this->payment->course->translation()?->title ?? '#'.$this->payment->course_id);
    }
}
