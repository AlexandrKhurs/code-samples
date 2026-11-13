<?php

namespace App\Modules\Notification\V1\Services\Sms;

use App\Modules\Notification\V1\Enums\NotificationDeliveryStatusEnum;
use App\Modules\Notification\V1\Notifications\Notifiable\NotifiablePhone;
use App\Modules\Notification\V1\Notifications\Notifiable\NotifiableUser;
use App\Modules\Notification\V1\Notifications\Notification;

class NullSmsService implements SmsServiceInterface
{
    public function send(NotifiableUser|NotifiablePhone $notifiable, Notification $notification): NotificationDeliveryStatusEnum|bool
    {
        return true;
    }
}
