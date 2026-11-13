<?php

namespace App\Modules\Notification\V1\Listeners;

use App\Modules\Notification\V1\Events\NotificationMessageCreatedEvent;
use App\Modules\Notification\V1\Services\NotificationMessage\NotificationMessageProcessor;

class NotificationMessageCreatedListener
{
    public function handle(NotificationMessageCreatedEvent $event): void
    {
        app(NotificationMessageProcessor::class)
            ->process($event->messageId);
    }
}
