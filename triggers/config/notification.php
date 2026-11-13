<?php

use App\Modules\Common\V1\Enums\QueueChannelEnum;

return [
    // all registered channels to be used in cascades
    'channels' => [
        App\Modules\Notification\V1\Notifications\Channels\DatabaseChannel::class,
        App\Modules\Notification\V1\Notifications\Channels\PushChannel::class,
        App\Modules\Notification\V1\Notifications\Channels\TelegramChannel::class,
        App\Modules\Notification\V1\Notifications\Channels\SmsChannel::class,
        App\Modules\Notification\V1\Notifications\Channels\MailChannel::class,
        // App\Modules\Notification\V1\Notifications\Channels\FlashCallChannel::class,
    ],

    // delivery status to this channel does not affect final message status
    // (like "we dont care about bd-notification as it's always delivered, look only at push/tg/sms statuses")
    'channels_ignored_for_message_final_status' => [
        App\Modules\Notification\V1\Notifications\Channels\DatabaseChannel::class,
    ],

    // all registered triggers
    'triggers' => [
        App\Modules\Notification\V1\NotificationTriggers\Event\EventCreatedTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventCancelledTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventRestoredTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventAvailableSlotTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventUpcomingTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventNotStartedTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventNotEndedTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventFullTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventRegistrationStartedTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventUpdatedTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventFinishedTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventCompletedTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventStartedTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventExpiredTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventEndingTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventOwnerChangedTrigger::class,

        App\Modules\Notification\V1\NotificationTriggers\Event\EventUserPaidTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventUserJoinedTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventUserKickedTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventUserCanceledTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventUserConfirmedTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventUserRejectedByLimitTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventUserTransferCompletedTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventUserSubscribedTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventUserUnsubscribedTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventUserAppliedTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventUserQueuedTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventUserInviteCreatedTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventUserInviteCanceledTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventUserInviteExpiredTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventUserInviteConfirmedTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventUserInviteDeclinedTrigger::class,

        App\Modules\Notification\V1\NotificationTriggers\Event\EventTeamCreatedTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventTeamCompletedTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventTeamUserJoinedTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventTeamJoinExpiredTrigger::class,
        App\Modules\Notification\V1\NotificationTriggers\Event\EventTeamUserRejectedByLimitTrigger::class,

        App\Modules\Notification\V1\NotificationTriggers\Pay\PaymentConsistencyErrorTrigger::class,

        App\Modules\Notification\V1\NotificationTriggers\Chat\ChatMessageCreatedTrigger::class,

        App\Modules\Notification\V1\NotificationTriggers\App\AppErrorTrigger::class,
    ],

    // 5 minutes strict idempotency (fix some cases when it's hard to avoid different events firing same trigger to be fired during single runtime)
    // (5 mins should be enough to process all "fire trigger" jobs from last run time
    'unique_id_ttl' => 60 * 5,

    // 1 minutes late check ttl (for triggers that support isStillFired() check)
    'fired_state_ttl' => 60 * 1,

    // queue all notification stuff is run on
    'queue' => QueueChannelEnum::NOTIFICATIONS->value,
];
