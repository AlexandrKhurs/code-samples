<?php

namespace App\Modules\Notification\V1\NotificationTriggers\App;

use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\Notification\V1\NotificationTriggers\Traits\TriggerHasUser;
use App\Modules\Notification\V1\NotificationTriggers\Trigger;
use App\Modules\Notification\V1\ValueObjects\NotificationMessageExtraData;
use Throwable;

class AppErrorTrigger extends Trigger
{
    use TriggerHasUser;

    public function __construct(private readonly string|null $e)
    {
    }

    public function type(): NotificationTypeEnum
    {
        return NotificationTypeEnum::APP_ERROR;
    }

    public function description(): ?string
    {
        return 'Ошибка';
    }

    public function filters(): array
    {
        return [];
    }

    public function applyFilters(array $filters): bool
    {
        return true;
    }

    public function recipients(): array
    {
        return [
            ...parent::recipients(),
        ];
    }

    public function recipientDescriptions(): array
    {
        return [
            ...parent::recipientDescriptions(),
        ];
    }

    public function variables(): array
    {
        return [
            ...parent::variables(),
            'error' => $this->e,
        ];
    }

    public function extraData(): NotificationMessageExtraData|null
    {
        return new NotificationMessageExtraData(
            triggerType: $this->type(),
        );
    }

    protected function uniqueIdData(): array
    {
        return [
            'type' => $this->type()->value,
        ];
    }
}
