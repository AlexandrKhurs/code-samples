<?php

namespace App\Modules\Notification\V1\Providers;

use App\Modules\Notification\V1\Services\NotificationMessage\NotificationMessageService;
use App\Modules\Notification\V1\Services\NotificationTemplate\NotificationTemplateService;
use App\Modules\Notification\V1\Services\NotificationTrigger\NotificationTriggerService;
use Illuminate\Support\ServiceProvider;

class NotificationTriggerProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(NotificationTriggerService::class, function () {
            return new NotificationTriggerService(
                availableNotificationTriggerClasses: config('notification.triggers'),
                notificationTemplateService: app(NotificationTemplateService::class),
                notificationMessageService: app(NotificationMessageService::class),
            );
        });
    }
}
