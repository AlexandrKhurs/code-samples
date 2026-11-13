<?php

namespace App\Modules\Notification\V1\Services\Telegram;

use App\Modules\Notification\V1\Enums\NotificationDeliveryStatusEnum;
use App\Modules\Notification\V1\Notifications\Notifiable\Notifiable;
use App\Modules\Notification\V1\Notifications\Notification;

interface TelegramServiceInterface
{
    public function send(Notifiable $notifiable, Notification $notification): NotificationDeliveryStatusEnum|bool;
}
