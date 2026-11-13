<?php

namespace App\Modules\Notification\V1\Enums;

enum NotificationVisibleEnum: int
{
    case PUBLIC = 0;
    case PRIVATE = 1;
}
