<?php

namespace App\Modules\Notification\V1\Services\NotificationDelivery;

use App\Modules\Common\V1\Helpers\FilterHelper;
use App\Modules\Common\V1\Helpers\Type;
use App\Modules\Notification\V1\Enums\NotificationDeliveryStatusEnum;
use App\Modules\Notification\V1\Events\NotificationDeliveryCreatedEvent;
use App\Modules\Notification\V1\Events\NotificationDeliveryStatusUpdatedEvent;
use App\Modules\Notification\V1\Models\NotificationDelivery;
use App\Modules\Notification\V1\Services\NotificationDelivery\Dto\CreateNotificationDeliveryDto;
use App\Modules\Notification\V1\Services\NotificationDelivery\Dto\UpdateNotificationDeliveryDto;

class NotificationDeliveryService
{
    public function __construct()
    {
    }

    public function getLastDeliveryToChannel(int $messageId, string $channelName): NotificationDelivery|null
    {
        return NotificationDelivery::query()
            ->where('message_id', $messageId)
            ->where('channel_name', $channelName)
            ->orderBy('id', 'desc')
            ->first();
    }

    public function create(CreateNotificationDeliveryDto $dto): NotificationDelivery
    {
        $delivery = NotificationDelivery::create(FilterHelper::getDefinedData([
            'message_id' => $dto->messageId,
            'channel_name' => $dto->channelName,
            'content' => $dto->content,
            'extra_data' => $dto->extraData,
            'status' => NotificationDeliveryStatusEnum::CREATED,
            'send_after' => $dto->sendAfter ?? now(),
            'timeout' => $dto->timeout,
            'sending_started_at' => null,
        ]));

        event(new NotificationDeliveryCreatedEvent($delivery->id));

        return $delivery;
    }

    public function update(int $deliveryId, UpdateNotificationDeliveryDto $dto): void
    {
        $delivery = NotificationDelivery::findOrFail($deliveryId);

        $log = Type::UNDEFINED;
        if ($dto->errorMessage !== Type::UNDEFINED) {
            $log = $delivery->log ?? collect();
            $log->push($this->formatLogMessage($dto->errorMessage));
        }

        $sendingStartedAt = Type::UNDEFINED;
        if (
            $delivery->status !== NotificationDeliveryStatusEnum::SENDING
            && $dto->status === NotificationDeliveryStatusEnum::SENDING
        ) {
            $sendingStartedAt = now();
        }

        NotificationDelivery::query()
            ->where('id', $deliveryId)
            ->update(FilterHelper::getDefinedData([
                'status' => $dto->status,
                'log' => $log,
                'sending_started_at' => $sendingStartedAt,
            ]));

        if ($dto->status !== Type::UNDEFINED) {
            event(new NotificationDeliveryStatusUpdatedEvent($deliveryId));
        }

        // TODO: maybe make separate event for "status changed from active to final" to optimize message processor calls
    }

    private function formatLogMessage(string $message): string
    {
        return now()->format('Y-m-d H:i:s') . ':   ' . $message;
    }
}
