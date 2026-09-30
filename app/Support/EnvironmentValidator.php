<?php

namespace App\Support;

/**
 * Fail-fast check at boot: ensures required env values exist.
 * Called from bootstrap (or a service provider) — throws on missing keys.
 */
class EnvironmentValidator
{
    /**
     * @return array<string, string[]> group => required env keys
     */
    public static function rules(): array
    {
        return [
            'DB' => ['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME'],
            'S3' => ['AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY', 'AWS_BUCKET'],
            'Fawry' => ['FAWRY_BASE_URL', 'FAWRY_MERCHANT_CODE', 'FAWRY_SECURITY_KEY', 'FAWRY_WEBHOOK_SECRET'],
            'Reverb' => ['REVERB_APP_ID', 'REVERB_APP_KEY', 'REVERB_APP_SECRET'],
            'Mail' => ['MAIL_MAILER', 'MAIL_FROM_ADDRESS'],
        ];
    }

    /**
     * @return array<string, string[]> missing keys per group (empty = ok)
     */
    public static function missing(): array
    {
        $missing = [];

        foreach (self::rules() as $group => $keys) {
            foreach ($keys as $key) {
                if (blank(env($key))) {
                    $missing[$group][] = $key;
                }
            }
        }

        return $missing;
    }

    public static function validate(): void
    {
        if (! app()->isProduction()) {
            return;
        }

        $missing = self::missing();

        if ($missing !== []) {
            throw new \RuntimeException('Missing required env keys: '.json_encode($missing));
        }
    }
}
