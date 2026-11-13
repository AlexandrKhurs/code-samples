<?php

namespace App\Modules\Notification\V1\Commands;

use App\Modules\Notification\V1\Models\NotificationDelivery;
use App\Modules\Notification\V1\Services\NotificationDelivery\NotificationDeliveryProcessor;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ProcessActiveNotificationDeliveriesCommand extends Command
{
    protected $signature = 'app:process-active-notification-deliveries';
    protected $description = 'Process active notification deliveries';

    public function handle(): void
    {
        NotificationDelivery::query()
            ->active()
            ->chunkById(50, function (EloquentCollection $deliveries) {
                foreach ($deliveries as $delivery) {
                    /** @var NotificationDelivery $delivery */
                    app(NotificationDeliveryProcessor::class)
                        ->process($delivery->id);
                }
            });
    }
}
