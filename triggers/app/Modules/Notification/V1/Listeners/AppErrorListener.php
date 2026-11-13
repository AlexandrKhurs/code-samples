<?php

namespace App\Modules\Notification\V1\Listeners;

use App\Modules\Common\V1\Events\AppErrorEvent;
use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\NotificationTriggers\App\AppErrorTrigger;
use Throwable;

class AppErrorListener
{
    public function handle(AppErrorEvent $event)
    {
        try {
            NotificationTrigger::fire(new AppErrorTrigger(
                e: $event->e
            ));
        } catch (Throwable) {
        }
    }
}
