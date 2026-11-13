<?php

namespace App\Modules\Notification\V1\Notifications\Interfaces;

interface HasNotificationDelivery
{
    public function getDeliveryId(): int;
}
