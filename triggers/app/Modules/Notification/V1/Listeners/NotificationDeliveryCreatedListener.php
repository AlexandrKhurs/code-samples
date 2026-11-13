<?php

namespace App\Modules\Notification\V1\Listeners;

use App\Modules\Notification\V1\Events\NotificationDeliveryCreatedEvent;
use App\Modules\Notification\V1\Services\NotificationDelivery\NotificationDeliveryProcessor;

class NotificationDeliveryCreatedListener
{
    public function handle(NotificationDeliveryCreatedEvent $event): void
    {
        app(NotificationDeliveryProcessor::class)
            ->process($event->deliveryId);
    }
}
