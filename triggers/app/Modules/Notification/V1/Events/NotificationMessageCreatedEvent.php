<?php

namespace App\Modules\Notification\V1\Events;

class NotificationMessageCreatedEvent
{
    public function __construct(
        public int $messageId
    ) {
    }
}
