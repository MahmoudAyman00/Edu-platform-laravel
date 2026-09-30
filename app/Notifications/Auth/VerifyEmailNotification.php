<?php

namespace App\Notifications\Auth;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly User $user)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim((string) config('app.frontend_url', config('app.url')), '/')
            .(string) config('app.frontend_verify_email_path', '/verify-email')
            .'?id='.$this->user->getKey().'&hash='.sha1($this->user->getEmailForVerification());

        return (new MailMessage)
            ->subject(__('messages.notif.verify_email_title'))
            ->greeting(__('messages.notif.verify_email_title'))
            ->line(__('messages.notif.verify_email_body', ['name' => $this->user->name]))
            ->action(__('messages.notif.verify_email_title'), $url);
    }
}
