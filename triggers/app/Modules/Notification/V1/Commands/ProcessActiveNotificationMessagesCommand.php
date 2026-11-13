<?php

namespace App\Modules\Notification\V1\Commands;

use App\Modules\Notification\V1\Models\NotificationMessage;
use App\Modules\Notification\V1\Services\NotificationMessage\NotificationMessageProcessor;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ProcessActiveNotificationMessagesCommand extends Command
{
    protected $signature = 'app:process-active-notification-messages';
    protected $description = 'Process active notification messages';

    public function handle(): void
    {
        NotificationMessage::query()
            ->active()
            ->chunkById(50, function (EloquentCollection $messages) {
                foreach ($messages as $message) {
                    /** @var NotificationMessage $message */
                    app(NotificationMessageProcessor::class)
                        ->process($message->id);
                }
            });
    }
}
