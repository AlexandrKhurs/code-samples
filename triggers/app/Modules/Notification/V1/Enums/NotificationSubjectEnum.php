<?php

namespace App\Modules\Notification\V1\Enums;

enum NotificationSubjectEnum: string
{
    case APP = 'app';
    case AUTH = 'auth';
    case NEWSLETTER = 'newsletter';
}
