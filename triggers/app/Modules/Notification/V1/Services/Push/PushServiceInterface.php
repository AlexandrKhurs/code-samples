<?php

namespace App\Modules\Notification\V1\Services\Push;

use App\Modules\Notification\V1\Enums\NotificationDeliveryStatusEnum;
use App\Modules\Notification\V1\Notifications\Notifiable\NotifiableUser;
use App\Modules\Notification\V1\Notifications\Notification;

interface PushServiceInterface
{
    public function send(NotifiableUser $notifiable, Notification $notification): NotificationDeliveryStatusEnum|bool|null;
}
