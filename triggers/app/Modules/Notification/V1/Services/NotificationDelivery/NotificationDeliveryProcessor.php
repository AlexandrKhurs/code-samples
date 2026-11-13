<?php

namespace App\Modules\Notification\V1\Services\NotificationDelivery;

use App\Modules\Notification\V1\Enums\NotificationDeliveryStatusEnum;
use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\Jobs\ProcessNotificationDeliveryJob;
use App\Modules\Notification\V1\Models\NotificationDelivery;
use App\Modules\Notification\V1\Notifications\Notifiable\NotifiableUser;
use App\Modules\Notification\V1\Notifications\Trigger\TriggerNotification;
use App\Modules\Notification\V1\Services\NotificationChannel\NotificationChannelService;
use App\Modules\Notification\V1\Services\NotificationDelivery\Dto\UpdateNotificationDeliveryDto;
use App\Modules\Notification\V1\Services\NotificationDelivery\NotificationDeliveryService;
use App\Modules\Notification\V1\Services\NotificationTrigger\NotificationTriggerService;
use Illuminate\Support\Facades\Notification;

class NotificationDeliveryProcessor
{
    public function __construct(
        public readonly NotificationDeliveryService $deliveryService,
    ) {
    }

    public function process(int $deliveryId)
    {
        $delivery = NotificationDelivery::findOrFail($deliveryId);
        if (empty($delivery)) {
            return;
        }

        dispatch(app(ProcessNotificationDeliveryJob::class, [
            'delivery' => $delivery,
        ]));
    }

    public function processImmediately(NotificationDelivery $delivery)
    {
        switch ($delivery->status) {
            case NotificationDeliveryStatusEnum::CREATED:
            case NotificationDeliveryStatusEnum::POSTPONED: // TODO: maybe queue to process immediately after $sendAfter and skip postponed ones here?
                if ($delivery->send_after?->isFuture()) {
                    $this->postpone($delivery);
                    break;
                }
                // TODO: maybe move this check to MessageProcessor and not create delivery at all if content is empty?
                // (downside: lack of logging, more complex cascade-processing logic in MessageProcessor)
                if ($this->isEmptyContent($delivery)) {
                    $this->skip($delivery, 'Empty content');
                    break;
                }
                if (!$this->isEnabledByUser($delivery)) {
                    $this->skip($delivery, 'Disabled by user');
                    break;
                }
                $this->send($delivery);
                break;
            case NotificationDeliveryStatusEnum::SENDING:
                if ($delivery->sending_timeout_expired) {
                    $this->expire($delivery);
                }
                break;
            case NotificationDeliveryStatusEnum::SENT:
            case NotificationDeliveryStatusEnum::DELIVERED:
            case NotificationDeliveryStatusEnum::READ:
            case NotificationDeliveryStatusEnum::FAILED:
            case NotificationDeliveryStatusEnum::SKIPPED:
                break;
        }
    }

    private function postpone(NotificationDelivery $delivery)
    {
        $this->deliveryService->update($delivery->id, new UpdateNotificationDeliveryDto(
            status: NotificationDeliveryStatusEnum::POSTPONED,
        ));
    }

    private function send(NotificationDelivery $delivery)
    {
        $recipient = $delivery->recipient;
        if (!$recipient) {
            $this->fail($delivery, 'Recipient not found');
            return;
        }

        $isTriggerStillFired = NotificationTrigger::isStillFired(
            $delivery->message?->trigger_type,
            $delivery->message?->trigger_reconstruction_data,
            $delivery->message?->template?->filters,
        );
        if (!$isTriggerStillFired) {
            $this->skip($delivery, 'Late check failed: trigger is no longer fired');
            return;
        }

        // TODO: maybe make some "withoutEvents' param to avoid unnecessary DeliveryUpdatedEvent processing?
        $this->deliveryService->update($delivery->id, new UpdateNotificationDeliveryDto(
            status: NotificationDeliveryStatusEnum::SENDING,
        ));

        $channelClass = app(NotificationChannelService::class)->getChannelClass($delivery->channel_name);
        if (!$channelClass) {
            $this->fail($delivery, 'Channel ' . $delivery->channel_name . ' not found');
            return;
        }

        Notification::send(
            new NotifiableUser([$recipient]),
            (new TriggerNotification($delivery))->toChannels([$channelClass])
        );
    }

    private function fail(NotificationDelivery $delivery, ?string $errorMessage = null)
    {
        $this->deliveryService->update($delivery->id, new UpdateNotificationDeliveryDto(
            status: NotificationDeliveryStatusEnum::FAILED,
            errorMessage: $errorMessage,
        ));
    }

    private function skip(NotificationDelivery $delivery, ?string $errorMessage = null)
    {
        $this->deliveryService->update($delivery->id, new UpdateNotificationDeliveryDto(
            status: NotificationDeliveryStatusEnum::SKIPPED,
            errorMessage: $errorMessage,
        ));
    }

    private function expire(NotificationDelivery $delivery)
    {
        $this->deliveryService->update($delivery->id, new UpdateNotificationDeliveryDto(
            status: NotificationDeliveryStatusEnum::FAILED,
            errorMessage: 'Timeout based on cascade settings',
        ));
    }

    private function isEmptyContent(NotificationDelivery $delivery): bool
    {
        // content may be [] or ['title' => '', 'text' => ''] - both are considered as empty
        return empty(array_filter($delivery->content ?? []));
    }

    private function isEnabledByUser(NotificationDelivery $delivery): bool
    {
        $trigger = app(NotificationTriggerService::class)->getTrigger($delivery->message->trigger_type);
        $triggerGroup = $trigger?->group();
        if (!$triggerGroup) {
            return false;
        }

        $channelName = $delivery->channel_name;
        if (!$channelName) {
            return false;
        }

        return $delivery->recipient?->notification_settings?->isEnabled(
            $triggerGroup->value,
            $channelName,
        );
    }
}
