<?php

namespace App\Modules\Notification\V1\Mappers;

use App\Modules\Notification\V1\Enums\NotificationTriggerGroupEnum;
use App\Modules\Notification\V1\Enums\NotificationTypeEnum;

class NotificationTriggerGroupMapper
{
    protected const GROUPS = [
        NotificationTriggerGroupEnum::CRITICAL->value => [
            NotificationTypeEnum::APP_ERROR,
            NotificationTypeEnum::EVENTS_CANCELED,
            NotificationTypeEnum::EVENTS_RESTORED,
            NotificationTypeEnum::EVENTS_NOT_STARTED,
            NotificationTypeEnum::EVENTS_NOT_ENDED,
            NotificationTypeEnum::EVENTS_EXPIRED,
            NotificationTypeEnum::EVENTS_ENDING,
            NotificationTypeEnum::EVENTS_USER_KICKED,
            NotificationTypeEnum::PAYMENTS_CONSISTENCY_ERROR,
        ],
        NotificationTriggerGroupEnum::MY_ACTIVITIES->value => [
            NotificationTypeEnum::EVENTS_CREATED,
            NotificationTypeEnum::EVENTS_FULL,
            NotificationTypeEnum::EVENTS_UPDATED,
            NotificationTypeEnum::EVENTS_REGISTRATION_STARTED,
            NotificationTypeEnum::EVENTS_FINISHED,
            NotificationTypeEnum::EVENTS_COMPLETED,
            NotificationTypeEnum::EVENTS_STARTED,
            NotificationTypeEnum::EVENTS_OWNER_CHANGED,
            NotificationTypeEnum::EVENTS_USER_JOINED,
            NotificationTypeEnum::EVENTS_USER_PAID,
            NotificationTypeEnum::EVENTS_USER_REJECTED_BY_LIMIT,
            NotificationTypeEnum::EVENTS_JOIN_EXPIRED,
            NotificationTypeEnum::EVENTS_USER_TRANSFER_COMPLETED,
            NotificationTypeEnum::EVENTS_USER_CANCELED,
            NotificationTypeEnum::EVENTS_USER_CONFIRMED,
            NotificationTypeEnum::EVENTS_USER_SUBSCRIBED,
            NotificationTypeEnum::EVENTS_USER_UNSUBSCRIBED,
            NotificationTypeEnum::EVENTS_USER_APPLIED,
            NotificationTypeEnum::EVENTS_USER_QUEUED,
            NotificationTypeEnum::EVENTS_USER_INVITE_CREATED,
            NotificationTypeEnum::EVENTS_USER_INVITE_CANCELED,
            NotificationTypeEnum::EVENTS_USER_INVITE_EXPIRED,
            NotificationTypeEnum::EVENTS_USER_INVITE_CONFIRMED,
            NotificationTypeEnum::EVENTS_USER_INVITE_DECLINED,
            NotificationTypeEnum::EVENTS_TEAM_CREATED,
            NotificationTypeEnum::EVENTS_TEAM_USER_JOINED,
            NotificationTypeEnum::EVENTS_TEAM_JOIN_EXPIRED,
            NotificationTypeEnum::EVENTS_TEAM_COMPLETED,
            NotificationTypeEnum::EVENTS_TEAM_USER_REJECTED_BY_LIMIT,
            NotificationTypeEnum::MESSAGES_CREATE,
        ],
        NotificationTriggerGroupEnum::NEWS->value => [
            NotificationTypeEnum::EVENTS_UPCOMING,
            NotificationTypeEnum::EVENTS_AVAILABLE_SLOT,
        ],
    ];

    public static function group(NotificationTypeEnum $type): NotificationTriggerGroupEnum|null
    {
        foreach (self::GROUPS as $groupValue => $types) {
            if (in_array($type, $types)) {
                return NotificationTriggerGroupEnum::from($groupValue);
            }
        }
        return null;
    }

    public static function types(NotificationTriggerGroupEnum $group): array
    {
        return self::GROUPS[$group->value] ?? [];
    }
}
