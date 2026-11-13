<?php

namespace App\Modules\Notification\V1\Notifications\Channels\Traits;

use App\Modules\Common\V1\Exceptions\SenderServiceFailedException;
use App\Modules\Common\V1\Helpers\Type;
use App\Modules\Notification\V1\Enums\NotificationDeliveryStatusEnum;
use App\Modules\Notification\V1\Notifications\Interfaces\HasNotificationDelivery;
use App\Modules\Notification\V1\Notifications\Notification;
use App\Modules\Notification\V1\Services\NotificationDelivery\Dto\UpdateNotificationDeliveryDto;
use App\Modules\Notification\V1\Services\NotificationDelivery\NotificationDeliveryService;
use Throwable;

trait UpdatesNotificationDeliveryStatusFromSendingResult
{
    public function updateNotificationDeliveryStatus(
        Notification $notification,
        Throwable|NotificationDeliveryStatusEnum|bool|null $result,
    ): void {
        $status = null;
        $logMessage = Type::UNDEFINED;

        switch (true) {
            case $result === true:
                $status = NotificationDeliveryStatusEnum::SENT;
                $logMessage = 'sent';
                break;
            case $result === false:
                $status = NotificationDeliveryStatusEnum::FAILED;
                $logMessage = 'failed';
                break;
            case $result instanceof NotificationDeliveryStatusEnum && in_array($result, NotificationDeliveryStatusEnum::successfulStatuses()):
                $status = $result;
                $logMessage = 'sent (' . $status->name . ')';
                break;
            case $result instanceof NotificationDeliveryStatusEnum:
                $status = $result;
                $logMessage = 'failed (' . $status->name . ')';
                break;
            case $result instanceof SenderServiceFailedException:
                $status = $result->getDeliveryStatus();
                $logMessage = 'failed (' . $status->name . ', ' . $result->getMessage() . ')';
                break;
            case $result instanceof Throwable:
                $status = NotificationDeliveryStatusEnum::FAILED;
                $logMessage = 'failed (' . $result->getMessage() . ')';
                break;
            default:
                $status = null;
                break;
        }
        if (empty($status)) {
            return;
        }

        $deliveryId = $notification instanceof HasNotificationDelivery ? $notification->getDeliveryId() : null;
        if (empty($deliveryId)) {
            return;
        }

        app(NotificationDeliveryService::class)->update($deliveryId, new UpdateNotificationDeliveryDto(
            status: $status,
            errorMessage: $logMessage,
        ));
    }
}
