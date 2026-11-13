<?php

namespace App\Modules\Notification\V1\ValueObjects;

/**
 * @property-read string $channelName
 * @property-read int $timeout seconds to expire while sending
 * @property-read ?self $onFail
 */
class NotificationChannelCascade
{
    public function __construct(
        public readonly string $channelName,
        public readonly int $timeout = 0,
        public readonly ?self $onFail = null,
    ) {
    }

    public static function fromArray(array $array): self
    {
        if (!self::isValidArray($array)) {
            throw new \InvalidArgumentException();
        }

        return new self(
            channelName: $array['channelName'],
            timeout: (int) ($array['timeout'] ?? 0),
            onFail: !empty($array['onFail']) ? self::fromArray($array['onFail']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'channelName' => $this->channelName,
            'timeout' => $this->timeout,
            'onFail' => $this->onFail?->toArray(),
        ];
    }

    public static function isValidArray(array $array): bool
    {
        return
            !empty($array['channelName']) && is_string($array['channelName'])
            && (empty($array['timeout']) || is_numeric($array['timeout']))
            && (empty($array['onFail']) || self::isValidArray($array['onFail']));
    }
}
