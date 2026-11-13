<?php

namespace App\Modules\Notification\V1\Providers;

use App\Modules\Notification\V1\Services\NotificationChannel\NotificationChannelService;
use Illuminate\Support\ServiceProvider;

class NotificationChannelsProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(NotificationChannelService::class, function () {
            return new NotificationChannelService(
                availableChannelClasses: config('notification.channels'),
                channelsIgnoredForMessageFinalStatus: config('notification.channels_ignored_for_message_final_status'),
            );
        });
    }
}
