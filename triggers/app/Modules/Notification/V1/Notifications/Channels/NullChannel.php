<?php

namespace App\Modules\Notification\V1\Notifications\Channels;

use App\Modules\Notification\V1\Notifications\Channels\Traits\UpdatesNotificationDeliveryStatusFromSendingResult;
use App\Modules\Notification\V1\Notifications\Notifiable\Notifiable;
use Illuminate\Notifications\Notification;

class NullChannel implements ChannelInterface
{
    use UpdatesNotificationDeliveryStatusFromSendingResult;

    public const NAME = 'null';

    public function send(Notifiable $notifiable, Notification $notification): void
    {
        $this->updateNotificationDeliveryStatus($notification, true);
    }
}
