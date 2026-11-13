<?php

namespace App\Modules\Notification\V1\Enums;

enum NotificationMessageStatusEnum: int
{
    case CREATED = 0;
    case SENDING = 1; // at least one channel sending is started (includint postponed deliveries)
    case SUCCESSFUL = 2; // sent at least to one channel (apart from db).. sent/delivered/read - see NotificationDelivery statuses
    case FAILED = 3; // failed to send to all channels (apart from db)
    case SKIPPED = 4; // fired but not sent due to uniqueness template settings (unique_for_period)

    public static function activeStatuses(): array
    {
        return [
            self::CREATED,
            self::SENDING,
        ];
    }

    public static function successfulStatuses(): array
    {
        return [
            self::SUCCESSFUL,
        ];
    }

    public static function failedStatuses(): array
    {
        return [
            self::FAILED,
            self::SKIPPED,
        ];
    }

    public function isSuccessful(): bool
    {
        return $this === self::SUCCESSFUL;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }
}
