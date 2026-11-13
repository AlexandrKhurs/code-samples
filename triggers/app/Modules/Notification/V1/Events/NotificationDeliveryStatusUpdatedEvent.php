<?php

namespace App\Modules\Notification\V1\Events;

/** NotificationDelivery's status updated */
class NotificationDeliveryStatusUpdatedEvent
{
    public function __construct(public int $deliveryId)
    {
    }
}
