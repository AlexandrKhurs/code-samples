<?php

namespace App\Modules\Notification\V1\Jobs;

use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\NotificationTriggers\Trigger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class FireNotificationTriggerJob implements ShouldQueue
{
    use Queueable;
    use InteractsWithQueue;

    public function __construct(
        public Trigger $trigger,
    ) {
        $this->onQueue($trigger->queue() ?? config('notification.queue'));
    }

    public function middleware(): array
    {
        return [
            // TODO: use WithoutOverlapping, BUT first of all think of best expire/release params and uniqueId() data for parallel workers usage
        ];
    }

    public function handle(): void
    {
        Log::info(sprintf(
            'FireNotificationTriggerJob: [trigger_type: %s, uniqueId: %s]',
            $this->trigger->type()->value,
            $this->trigger->uniqueId(),
        ));

        NotificationTrigger::fireImmediately($this->trigger);
    }
}
