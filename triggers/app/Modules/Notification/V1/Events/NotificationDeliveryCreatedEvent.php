<?php

namespace App\Modules\Notification\V1\Events;

class NotificationDeliveryCreatedEvent
{
    public function __construct(public int $deliveryId)
    {
    }
}
