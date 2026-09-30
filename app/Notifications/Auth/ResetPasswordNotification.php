<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim((string) config('app.frontend_url', config('app.url')), '/')
            .(string) config('app.frontend_reset_password_path', '/reset-password')
            .'?token='.$this->token.'&email='.urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject(__('messages.notif.reset_password_title'))
            ->greeting(__('messages.notif.reset_password_title'))
            ->line(__('messages.notif.reset_password_body'))
            ->action(__('messages.notif.reset_password_title'), $url);
    }
}
