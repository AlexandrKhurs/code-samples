<?php

namespace App\Modules\Notification\V1\Listeners;

use App\Modules\Event\V1\Events\EventCreatedEvent;
use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\NotificationTriggers\Event\EventCreatedTrigger;

class EventCreatedListener
{
    public function handle(EventCreatedEvent $event): void
    {
        $gameEvent = $event->event;
        if (!$gameEvent) {
            return;
        }

        NotificationTrigger::fire(new EventCreatedTrigger(eventId: $gameEvent->id));
    }
}
