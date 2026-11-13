<?php

namespace App\Modules\Notification\V1\Services\NotificationDelivery\Dto;

use App\Modules\Notification\V1\Enums\NotificationDeliveryStatusEnum;
use App\Modules\Common\V1\Helpers\Type;

class UpdateNotificationDeliveryDto
{
    public function __construct(
        public readonly NotificationDeliveryStatusEnum|Type $status = Type::UNDEFINED,
        public readonly string|Type $errorMessage = Type::UNDEFINED,
    ) {
    }
}
