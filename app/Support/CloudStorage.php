<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Single wrapper around object storage (S3 in production).
 * Used by Courses (upload), Enrollments (playback) and Certificates (PDF).
 */
class CloudStorage
{
    public static function disk(): string
    {
        return (string) config('content.video_disk', 's3');
    }

    public static function presignedUploadUrl(string $path, int $ttlSeconds = 3600, array $options = []): string
    {
        $disk = Storage::disk(self::disk());

        if (method_exists($disk, 'temporaryUploadUrl')) {
            try {
                $url = $disk->temporaryUploadUrl($path, now()->addSeconds($ttlSeconds), $options);

                // S3-style drivers return ['url' => ..., 'headers' => ...].
                return is_array($url) ? (string) ($url['url'] ?? reset($url)) : (string) $url;
            } catch (\Throwable) {
                // Local/dev drivers don't support temporary upload URLs.
            }
        }

        return $disk->url($path);
    }

    public static function presignedDownloadUrl(string $path, ?int $ttlSeconds = null): string
    {
        $ttlSeconds ??= (int) config('content.signed_url_ttl', 3600);
        $disk = Storage::disk(self::disk());

        if (method_exists($disk, 'temporaryUrl')) {
            try {
                return $disk->temporaryUrl($path, now()->addSeconds($ttlSeconds));
            } catch (\Throwable) {
                // Local/dev drivers don't support temporary URLs.
            }
        }

        return $disk->url($path);
    }

    public static function store(string $path, string $contents, array $options = []): bool
    {
        return Storage::disk(self::disk())->put($path, $contents, $options);
    }

    public static function delete(string $path): bool
    {
        return Storage::disk(self::disk())->delete($path);
    }
}
