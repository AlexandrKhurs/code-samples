<?php

namespace App\Modules\Notification\V1\Services\NotificationCascade\Dto;

use App\Modules\Common\V1\Helpers\Type;

class UpdateNotificationCascadeDto
{
    public function __construct(
        public readonly int $id,
        public readonly string|Type $name = Type::UNDEFINED,
        public readonly array|Type $silentTimePeriods = Type::UNDEFINED,
        public readonly array|Type $channelCascades = Type::UNDEFINED,
    ) {
    }
}
