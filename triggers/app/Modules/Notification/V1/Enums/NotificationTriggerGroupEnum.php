<?php

namespace App\Modules\Notification\V1\Enums;

enum NotificationTriggerGroupEnum: string
{
    case CRITICAL = 'critical';
    case MY_ACTIVITIES = 'my_activities';
    case NEWS = 'news';
}
