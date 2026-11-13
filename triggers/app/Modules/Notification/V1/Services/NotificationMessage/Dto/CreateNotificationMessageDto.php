<?php

namespace App\Modules\Notification\V1\Services\NotificationMessage\Dto;

use App\Modules\Notification\V1\Enums\NotificationMessageStatusEnum;
use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\Notification\V1\ValueObjects\NotificationMessageExtraData;

class CreateNotificationMessageDto
{
    public function __construct(
        public readonly NotificationTypeEnum $triggerType,
        public readonly int $templateId,
        public readonly int $recipientId,
        public readonly int $cascadeId,
        public readonly array $content, // TODO: типизировать как-то (VO с динамическим набором данных для каждого канала???)
        public readonly NotificationMessageExtraData|null $extraData = null,
        public readonly NotificationMessageStatusEnum $status = NotificationMessageStatusEnum::CREATED,
        public readonly array|null $triggerReconstructionData = null,
    ) {
    }
}
