<?php

namespace App\Modules\Notification\V1\NotificationTriggers\Event;

use App\Modules\Event\V1\Enums\EventSubscriptionTypeEnum;
use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\Notification\V1\NotificationTriggers\BaseTriggers\BaseEventTrigger;

class EventAvailableSlotTrigger extends BaseEventTrigger
{
    public function type(): NotificationTypeEnum
    {
        return NotificationTypeEnum::EVENTS_AVAILABLE_SLOT;
    }

    public function recipients(): array
    {
        return [
            ...parent::recipients(),
            'event.subscribers' => fn() => $this->event
                ?->eventSubscriptions
                ->where('type', EventSubscriptionTypeEnum::AVAILABLE_SLOT)
                ->pluck('user'),
        ];
    }

    public function description(): ?string
    {
        return 'Свободное место (для подписчиков на лист ожидания)';
    }
}
