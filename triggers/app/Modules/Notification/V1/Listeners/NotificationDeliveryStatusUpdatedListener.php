<?php

namespace App\Modules\Notification\V1\Listeners;

use App\Modules\Notification\V1\Events\NotificationDeliveryStatusUpdatedEvent;
use App\Modules\Notification\V1\Models\NotificationDelivery;
use App\Modules\Notification\V1\Services\NotificationMessage\NotificationMessageProcessor;

class NotificationDeliveryStatusUpdatedListener
{
    public function handle(NotificationDeliveryStatusUpdatedEvent $event)
    {
        $delivery = NotificationDelivery::find($event->deliveryId);
        if (!$delivery) {
            return;
        }

        // analyze parent message state and take actions if needed
        app(NotificationMessageProcessor::class)
            ->process($delivery->message_id);
    }
}
