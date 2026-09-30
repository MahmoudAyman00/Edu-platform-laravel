<?php

namespace App\Exceptions;

use Exception;

/**
 * Application exception carrying a translation key (lang/messages.php)
 * instead of a hardcoded message.
 */
class AppException extends Exception
{
    public function __construct(
        public readonly string $messageKey,
        public readonly string $errorCode,
        public readonly int $httpStatus = 400,
        public readonly array $params = [],
    ) {
        parent::__construct($messageKey, $httpStatus);
    }

    public static function fromKey(string $messageKey, string $errorCode, int $httpStatus = 400, array $params = []): self
    {
        return new self($messageKey, $errorCode, $httpStatus, $params);
    }

    public function translatedMessage(): string
    {
        return __($this->messageKey, $this->params);
    }
}
