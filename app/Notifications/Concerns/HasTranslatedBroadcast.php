<?php

namespace App\Notifications\Concerns;

/**
 * Broadcast payload builder.
 *
 * Same key + params as the database payload, plus title/body translated
 * to the NOTIFIABLE's locale (queue workers have no request locale,
 * so NotificationResource's request-based translation doesn't apply).
 * The database payload (toArray) is left untouched.
 */
trait HasTranslatedBroadcast
{
    /**
     * @param  array{key: string, params?: array<string, mixed>}  $base
     */
    protected function broadcastPayload(object $notifiable, array $base): array
    {
        $locale = method_exists($notifiable, 'preferredLocale')
            ? (string) $notifiable->preferredLocale()
            : (string) config('app.fallback_locale', 'ar');

        $key = $base['key'];
        $params = $base['params'] ?? [];

        return array_merge($base, [
            'title' => __("messages.notif.{$key}_title", $params, $locale),
            'body' => __("messages.notif.{$key}_body", $params, $locale),
        ]);
    }
}
