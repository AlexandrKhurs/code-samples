<?php

namespace App\Modules\Notification\V1\Tests\Triggers;

use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\Notification\V1\NotificationTriggers\Trigger;
use App\Modules\Notification\V1\ValueObjects\NotificationMessageExtraData;

class TestTrigger extends Trigger
{
    public function type(): NotificationTypeEnum
    {
        return NotificationTypeEnum::EVENTS_USER_JOINED;
    }

    public function recipients(): array
    {
        return [];
    }

    public function filters(): array
    {
        return [];
    }

    public function variables(): array
    {
        return [];
    }

    public function extraData(): NotificationMessageExtraData
    {
        return new NotificationMessageExtraData();
    }

    public function applyFilters(array $filters): bool
    {
        return true;
    }
}
