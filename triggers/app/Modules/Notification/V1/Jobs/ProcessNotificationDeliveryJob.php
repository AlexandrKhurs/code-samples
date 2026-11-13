<?php

namespace App\Modules\Notification\V1\Jobs;

use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\Models\NotificationDelivery;
use App\Modules\Notification\V1\Services\NotificationDelivery\NotificationDeliveryProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessNotificationDeliveryJob implements ShouldQueue
{
    use Queueable;
    use InteractsWithQueue;

    public function __construct(
        public NotificationDelivery $delivery,
    ) {
        $triggerQueue = NotificationTrigger::getTrigger($delivery?->message?->trigger_type)?->queue();
        $this->onQueue($triggerQueue ?? config('notification.queue'));
    }

    public function middleware(): array
    {
        return [
            // TODO: use WithoutOverlapping, BUT first of all think of best expire/release params for parallel workers usage
        ];
    }

    public function handle(): void
    {
        Log::info(sprintf(
            'ProcessNotificationDeliveryJob: [delivery_id: %s, message_id: %s, user_id: %s, trigger_type: %s, channel: %s]',
            $this->delivery->id,
            $this->delivery->message?->id,
            $this->delivery->message?->recipient_id,
            $this->delivery->message?->trigger_type?->value,
            $this->delivery->channel_name,
        ));

        app(NotificationDeliveryProcessor::class)
            ->processImmediately($this->delivery);
    }
}
