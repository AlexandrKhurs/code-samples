<?php

namespace App\Modules\Notification\V1\Services\NotificationTemplate\Dto;

use App\Modules\Notification\V1\Enums\NotificationTypeEnum;

class CreateNotificationTemplateDto
{
    public function __construct(
        public readonly string $name,
        public readonly bool $active,
        public readonly NotificationTypeEnum $triggerType,
        public readonly array $filters,
        public readonly array $recipients,
        public readonly array $recipientsExcluded,
        public readonly array $content,
        public readonly int $uniqueForPeriod,
        public readonly int $cascadeId,
        public readonly string|null $utmCampaign = null,
        public readonly string|null $utmContent = null,
    ) {
    }
}
