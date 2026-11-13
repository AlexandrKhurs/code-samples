<?php

namespace App\Modules\Notification\V1\Listeners;

use App\Modules\Event\V1\Events\EventCancelledEvent;
use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\NotificationTriggers\Event\EventCancelledTrigger;

class EventCanceledListener
{
    public function handle(EventCancelledEvent $event): void
    {
        $gameEvent = $event->event;
        if (!$gameEvent) {
            return;
        }

        NotificationTrigger::fire(new EventCancelledTrigger(
            eventId: $gameEvent->id
        ));
    }
}
