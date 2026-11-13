<?php

namespace App\Modules\Notification\V1\ValueObjects;

use App\Modules\Common\V1\Helpers\FilterHelper;
use App\Modules\Common\V1\Helpers\Type;
use App\Modules\Event\V1\Enums\EventTypeEnum;
use App\Modules\Notification\V1\Enums\NotificationTypeEnum;

class NotificationMessageExtraData
{
    public function __construct(
        public readonly NotificationTypeEnum|Type $triggerType = Type::UNDEFINED,
        public readonly int|Type $eventId = Type::UNDEFINED,
        public readonly string|Type $eventSlug = Type::UNDEFINED,
        public readonly EventTypeEnum|Type $eventType = Type::UNDEFINED,
        public readonly int|Type $chatId = Type::UNDEFINED, // for chat-dependent notifications only
        public readonly string|Type $uuid = Type::UNDEFINED,
    ) {
    }

    public function toArray(): array
    {
        return FilterHelper::getDefinedData([
            'trigger_type' => $this->triggerType instanceof NotificationTypeEnum ? $this->triggerType->value : Type::UNDEFINED,
            'event_id' => $this->eventId,
            'event_slug' => $this->eventSlug,
            'event_type' => $this->eventType instanceof EventTypeEnum ? $this->eventType->value : Type::UNDEFINED,
            'chat_id' => $this->chatId,
            'uuid' => $this->uuid,
        ]);
    }

    public static function fromArray(array|null $array): self
    {
        return new self(
            triggerType: is_string($array['trigger_type'] ?? null) ? NotificationTypeEnum::from($array['trigger_type']) : Type::UNDEFINED,
            eventId: is_numeric($array['event_id'] ?? null) ? (int) $array['event_id'] : Type::UNDEFINED,
            eventSlug: is_string($array['event_slug'] ?? null) ? $array['event_slug'] : Type::UNDEFINED,
            eventType: is_numeric($array['event_type'] ?? null) ? EventTypeEnum::from($array['event_type']) : Type::UNDEFINED,
            chatId: is_numeric($array['chat_id'] ?? null) ? (int) $array['chat_id'] : Type::UNDEFINED,
            uuid: is_string($array['uuid'] ?? null) ? $array['uuid'] : Type::UNDEFINED,
        );
    }

    public static function merge(?self $extraData, ?array $additionalData): self
    {
        return self::fromArray([
            ...($extraData?->toArray() ?? []),
            ...FilterHelper::getDefinedData($additionalData ?? []),
        ]);
    }
}
