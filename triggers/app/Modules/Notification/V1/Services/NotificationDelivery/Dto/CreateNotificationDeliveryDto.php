<?php

namespace App\Modules\Notification\V1\Services\NotificationDelivery\Dto;

use Carbon\Carbon;
use App\Modules\Notification\V1\ValueObjects\NotificationMessageExtraData;

class CreateNotificationDeliveryDto
{
    public function __construct(
        public readonly int $messageId,
        public readonly string $channelName,
        public readonly array $content,
        public readonly NotificationMessageExtraData|null $extraData = null,
        public readonly Carbon|null $sendAfter = null,
        public readonly int $timeout = 0,
    ) {
    }
}
