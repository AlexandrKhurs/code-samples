<?php

namespace App\Modules\Notification\V1\Services\Push\Dto;

use App\Modules\Common\V1\Helpers\Type;
use App\Modules\Event\V1\Enums\EventTypeEnum;
use App\Modules\Notification\V1\Enums\NotificationTypeEnum;

class PushMessageDto
{
    public function __construct(
        public readonly string $body,
        public readonly NotificationTypeEnum|Type $type = Type::UNDEFINED,
        public readonly string|Type $title = Type::UNDEFINED,
        public readonly string|Type $imageUrl = Type::UNDEFINED,
        public readonly int|Type $eventId = Type::UNDEFINED,
        public readonly string|Type $eventSlug = Type::UNDEFINED,
        public readonly EventTypeEnum|Type $eventType = Type::UNDEFINED,
        public readonly int|Type $chatId = Type::UNDEFINED,
        public readonly string|Type $uuid = Type::UNDEFINED,
    ) {
    }
}
