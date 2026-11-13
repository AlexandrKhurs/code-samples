<?php

namespace App\Modules\Notification\V1\Services\NotificationCascade\Dto;

class CreateNotificationCascadeDto
{
    public function __construct(
        public readonly string $name,
        public readonly array $silentTimePeriods = [],
        public readonly array $channelCascades = [],
    ) {
    }
}
