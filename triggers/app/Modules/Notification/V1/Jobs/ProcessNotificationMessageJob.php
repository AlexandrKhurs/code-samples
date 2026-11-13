<?php

namespace App\Modules\Notification\V1\Jobs;

use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\Models\NotificationMessage;
use App\Modules\Notification\V1\Services\NotificationMessage\NotificationMessageProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessNotificationMessageJob implements ShouldQueue
{
    use Queueable;
    use InteractsWithQueue;

    public function __construct(
        public NotificationMessage $message,
    ) {
        $triggerQueue = NotificationTrigger::getTrigger($message->trigger_type)?->queue();
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
            'ProcessNotificationMessageJob: [message_id: %s, user_id: %s, trigger_type: %s]',
            $this->message->id,
            $this->message->recipient_id,
            $this->message->trigger_type?->value,
        ));

        app(NotificationMessageProcessor::class)
            ->processImmediately($this->message);
    }
}
