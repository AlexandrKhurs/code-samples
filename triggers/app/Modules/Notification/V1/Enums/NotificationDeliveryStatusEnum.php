<?php

namespace App\Modules\Notification\V1\Enums;

enum NotificationDeliveryStatusEnum: int
{
    case CREATED = 0; // sending is queued to be sent
    case POSTPONED = 1; // to be sent after silent period as set in template
    case SENDING = 2; // sending started, in progress
    case SENT = 3; // sent to the channel
    case DELIVERED = 4; // delivered to the channel
    case READ = 5; // read by the user
    case FAILED = 6; // failed to send to the channel
    case SKIPPED = 7; // skipped due to user's settings (considered as failed)

    // TODO: we SHOULD NOT define global "successful/failed/active/final" statuses for NotificationDelivery!!
    //       it depends on CHANNEL (for some channel Sent is final and successfull, for others - Sent is active and intermediate (NOT successful nor failed)),
    //       better to make some mapper for this (not service, because Model should use it for scopes, but model SHOULD NOT depend on service)..
    //       or maybe leave it in Service, but use Delivery Repository for filtering (not model's scopes)..
    public static function successfulStatuses(): array
    {
        return [
            self::SENT,
            self::DELIVERED,
            self::READ,
        ];
    }

    public static function failedStatuses(): array
    {
        return [
            self::FAILED,
            self::SKIPPED,
        ];
    }

    public static function activeStatuses(): array
    {
        return [
            self::CREATED,
            self::POSTPONED, // TODO: later make it "sleeping" status to avoid extra check by processor
            self::SENDING,
        ];
    }

    public static function pendingStatuses(): array
    {
        return [
            self::CREATED,
            self::POSTPONED,
        ];
    }

    public static function finalStatuses(): array
    {
        return [
            ...self::successfulStatuses(),
            ...self::failedStatuses(),
        ];
    }

    public function isSuccessful(): bool
    {
        return in_array($this, self::successfulStatuses());
    }

    public function isFailed(): bool
    {
        return in_array($this, self::failedStatuses());
    }

    public function isFinal(): bool
    {
        return in_array($this, self::finalStatuses());
    }
}
