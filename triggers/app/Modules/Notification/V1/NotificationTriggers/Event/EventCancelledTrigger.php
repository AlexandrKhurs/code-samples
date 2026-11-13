<?php

namespace App\Modules\Notification\V1\NotificationTriggers\Event;

use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\Notification\V1\NotificationTriggers\BaseTriggers\BaseEventTrigger;

class EventCancelledTrigger extends BaseEventTrigger
{
    public function type(): NotificationTypeEnum
    {
        return NotificationTypeEnum::EVENTS_CANCELED;
    }

    public function description(): ?string
    {
        return 'Событие отменено организатором';
    }
}
