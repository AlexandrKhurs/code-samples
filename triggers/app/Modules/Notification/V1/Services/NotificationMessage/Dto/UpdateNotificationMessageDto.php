<?php

namespace App\Modules\Notification\V1\Services\NotificationMessage\Dto;

use App\Modules\Common\V1\Helpers\Type;
use App\Modules\Notification\V1\Enums\NotificationMessageStatusEnum;

class UpdateNotificationMessageDto
{
    public function __construct(
        public readonly array|Type $content = Type::UNDEFINED, // TODO: типизировать как-то (VO с динамическим набором данных для каждого канала???)
        public readonly NotificationMessageExtraData|Type $extraData = Type::UNDEFINED,
        public readonly NotificationMessageStatusEnum|Type $status = Type::UNDEFINED,
    ) {
    }
}
