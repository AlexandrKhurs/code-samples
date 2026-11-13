<?php

namespace App\Modules\Notification\V1\Services\NotificationTemplate\Dto;

use App\Modules\Common\V1\Helpers\Type;
use App\Modules\Notification\V1\Enums\NotificationTypeEnum;

class UpdateNotificationTemplateDto
{
    public function __construct(
        public readonly int $id,
        public readonly string|null|Type $name = Type::UNDEFINED,
        public readonly bool|Type $active = Type::UNDEFINED,
        public readonly NotificationTypeEnum|Type $triggerType = Type::UNDEFINED,
        public readonly array|Type $recipients = Type::UNDEFINED,
        public readonly array|Type $recipientsExcluded = Type::UNDEFINED,
        public readonly array|Type $content = Type::UNDEFINED,
        public readonly array|Type $filters = Type::UNDEFINED,
        public readonly int|Type $uniqueForPeriod = Type::UNDEFINED,
        public readonly int|Type $cascadeId = Type::UNDEFINED,
        public readonly string|null|Type $utmCampaign = Type::UNDEFINED,
        public readonly string|null|Type $utmContent = Type::UNDEFINED,
    ) {
    }
}
