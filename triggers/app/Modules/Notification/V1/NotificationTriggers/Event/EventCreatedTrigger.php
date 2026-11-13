<?php

namespace App\Modules\Notification\V1\NotificationTriggers\Event;

use App\Modules\Common\V1\Enums\QueueChannelEnum;
use App\Modules\Event\V1\Enums\EventStatusEnum;
use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\Notification\V1\NotificationTriggers\BaseTriggers\BaseEventTrigger;
use App\Modules\User\V1\Dtos\GetSuitableUsersDto;
use App\Modules\User\V1\Repositories\UserRepository;

class EventCreatedTrigger extends BaseEventTrigger
{
    public function type(): NotificationTypeEnum
    {
        return NotificationTypeEnum::EVENTS_CREATED;
    }

    public function queue(): ?string
    {
        return QueueChannelEnum::NOTIFICATIONS_LOW_PRIORITY->value; // to send to big list of suitable_users
    }

    public function recipients(): array
    {
        // parent recipients are NOT USED HERE!
        return [
            ...$this->adminRecipients(),
            'event.manager' => parent::recipients()['event.manager'],
            'event.suitable_users' => fn() => app(UserRepository::class)->getSuitableForEvent(new GetSuitableUsersDto(eventId: $this->event->id)),
        ];
    }

    public function excludedRecipients(): array
    {
        // NO "suitable_users" here (because excludedRecipients less optimized for huge amounts)
        return [
            'event.manager',
        ];
    }

    public function recipientDescriptions(): array
    {
        // parent recipient descriptions are NOT USED HERE!
        return [
            'event.suitable_users' => 'РАССЫЛКА ПО ВСЕМ ПОДХОДЯЩИМ ЮЗЕРАМ',
        ];
    }

    public function description(): ?string
    {
        return 'Событие создано';
    }

    // late check for slow big newsletters (to avoid sending too many messages)
    public function isStillFired(): bool
    {
        return $this->event->status === EventStatusEnum::UPCOMING;
    }

    // TODO: think of supporting toReconstructionData() in parent base trigger??
    public function toReconstructionData(): array|null
    {
        return [
            'eventId' => $this->event?->id,
        ];
    }
}
