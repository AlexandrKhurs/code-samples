<?php

namespace App\Modules\Notification\V1\Notifications\Trigger;

use App\Modules\Common\V1\Helpers\Type;
use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\Mail\AppMail;
use App\Modules\Notification\V1\Models\NotificationDelivery;
use App\Modules\Notification\V1\Notifications\Interfaces\HasNotificationDelivery;
use App\Modules\Notification\V1\Notifications\Notification;
use App\Modules\Notification\V1\Services\Notification\Dto\DatabaseNotificationDto;
use App\Modules\Notification\V1\Services\NotificationChannel\NotificationChannelService;
use App\Modules\Notification\V1\Services\Push\Dto\PushMessageDto;
use Illuminate\Mail\Mailable;

class TriggerNotification extends Notification implements HasNotificationDelivery
{
    public function __construct(
        public readonly NotificationDelivery $delivery,
    ) {
        $this->type =
            $delivery->extra_data?->triggerType instanceof NotificationTypeEnum
            ? $delivery->extra_data?->triggerType
            : NotificationTypeEnum::APP;
    }

    public function via(object $notifiable): array
    {
        $channelService = app(NotificationChannelService::class);
        return $this->channelsFilter(
            $notifiable,
            $channelService->getChannelClasses(),
        );
    }

    public function getDeliveryId(): int
    {
        return $this->delivery->id;
    }

    public function toDatabase(): ?DatabaseNotificationDto
    {
        return new DatabaseNotificationDto(
            type: $this->type,
            title: $this->delivery->content['title'] ?? Type::UNDEFINED,
            text: $this->delivery->content['text'],
            eventId: $this->delivery->extra_data?->eventId ?? Type::UNDEFINED,
            eventSlug: $this->delivery->extra_data?->eventSlug ?? Type::UNDEFINED,
            eventType: $this->delivery->extra_data?->eventType ?? Type::UNDEFINED,
            chatId: $this->delivery->extra_data?->chatId ?? Type::UNDEFINED,
            uuid: $this->delivery->extra_data?->uuid ?? Type::UNDEFINED,
        );
    }

    public function toPush(): ?PushMessageDto
    {
        return new PushMessageDto(
            type: $this->type,
            title: $this->delivery->content['title'],
            body: $this->delivery->content['body'],
            eventId: $this->delivery->extra_data?->eventId ?? Type::UNDEFINED,
            eventSlug: $this->delivery->extra_data?->eventSlug ?? Type::UNDEFINED,
            eventType: $this->delivery->extra_data?->eventType ?? Type::UNDEFINED,
            chatId: $this->delivery->extra_data?->chatId ?? Type::UNDEFINED,
            uuid: $this->delivery->extra_data?->uuid ?? Type::UNDEFINED,
        );
    }

    public function toTelegram(): ?string
    {
        return $this->delivery->content['message'] ?? null;
    }

    public function toSms(): ?string
    {
        return $this->delivery->content['message'] ?? null;
    }

    // TODO: complete this (now it only supports generic AppMail with body only allowed)
    public function toMail(): ?Mailable
    {
        return
            !empty($this->delivery->content['body'])
            ? new AppMail($this->delivery->content['body'])
            : null;
    }

    public function toFlashCall(): ?string
    {
        return null;
    }

    public function viaQueues(): array
    {
        $defaultQueues = parent::viaQueues();
        $trigger = NotificationTrigger::getTrigger($this?->delivery?->message?->trigger_type);
        $triggerQueue = $trigger?->queue();

        if (empty($triggerQueue)) {
            return $defaultQueues;
        }

        return array_map(fn($queue) => $triggerQueue, $defaultQueues);
    }
}
