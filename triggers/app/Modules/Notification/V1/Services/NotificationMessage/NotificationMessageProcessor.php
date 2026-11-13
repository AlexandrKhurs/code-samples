<?php

namespace App\Modules\Notification\V1\Services\NotificationMessage;

use App\Modules\Common\V1\Helpers\Type;
use App\Modules\Notification\V1\Enums\NotificationDeliveryStatusEnum;
use App\Modules\Notification\V1\Enums\NotificationMessageStatusEnum;
use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\Jobs\ProcessNotificationMessageJob;
use App\Modules\Notification\V1\Models\NotificationDelivery;
use App\Modules\Notification\V1\Models\NotificationMessage;
use App\Modules\Notification\V1\Services\NotificationCascade\NotificationCascadeService;
use App\Modules\Notification\V1\Services\NotificationChannel\NotificationChannelService;
use App\Modules\Notification\V1\Services\NotificationDelivery\Dto\CreateNotificationDeliveryDto;
use App\Modules\Notification\V1\Services\NotificationDelivery\NotificationDeliveryService;
use App\Modules\Notification\V1\Services\NotificationMessage\Dto\UpdateNotificationMessageDto;
use App\Modules\Notification\V1\Services\NotificationMessage\NotificationMessageService;
use App\Modules\Notification\V1\Services\NotificationTemplate\NotificationTemplateService;
use App\Modules\Notification\V1\ValueObjects\NotificationChannelCascade;
use App\Modules\Notification\V1\ValueObjects\NotificationMessageExtraData;
use Carbon\Carbon;

class NotificationMessageProcessor
{
    public function __construct(
        public readonly NotificationMessageService $notificationMessageService,
        public readonly NotificationDeliveryService $notificationDeliveryService,
        public readonly NotificationTemplateService $notificationTemplateService,
        public readonly NotificationChannelService $notificationChannelService,
        public readonly NotificationCascadeService $notificationCascadeService,
    ) {
    }

    /** analyze message and dispatch deliveries to channels if needed */
    public function process(int $messageId): void
    {
        /** @var NotificationMessage $message */
        $message = NotificationMessage::findOrFail($messageId);
        if (!$message->is_active) {
            return;
        }

        dispatch(app(ProcessNotificationMessageJob::class, [
            'message' => $message,
        ]));
    }

    public function processImmediately(NotificationMessage $message): void
    {
        if (!$message->template || !$message->cascade) {
            $this->notificationMessageService->update($message->id, new UpdateNotificationMessageDto(
                status: NotificationMessageStatusEnum::FAILED,
            ));
            return;
        }

        // check cooldown period (skip if not unique for period)
        if (
            $message->template?->unique_for_period > 0
            && $message->is_new
            && !$this->notificationMessageService->isUniqueForPeriod($message)
        ) {
            $this->notificationMessageService->update($message->id, new UpdateNotificationMessageDto(
                status: NotificationMessageStatusEnum::SKIPPED,
            ));
            return;
        }

        if (!$message->is_active) {
            return;
        }

        // message is ACTIVE here - process it (analyze each cascade thread and update status)

        $isStillFired = NotificationTrigger::isStillFired(
            $message->trigger_type,
            $message->trigger_reconstruction_data,
            $message->template?->filters,
        );
        if (!$isStillFired && $message->is_created && $message->deliveries->isEmpty()) {
            $this->notificationMessageService->update($message->id, new UpdateNotificationMessageDto(
                status: NotificationMessageStatusEnum::SKIPPED,
            ));
            return;
        }

        foreach ($message->cascade->channel_cascades as $channelCascade) {
            $this->processChannelCascade($channelCascade, $message);
        }

        $messageStatus = $this->calculateMessageStatus($message);
        if ($messageStatus !== $message->status) {
            $this->notificationMessageService->update($message->id, new UpdateNotificationMessageDto(
                status: $messageStatus,
            ));
        }
    }

    private function processChannelCascade(NotificationChannelCascade $channelCascade, NotificationMessage $message): void
    {
        $lastDelivery = $this->notificationDeliveryService->getLastDeliveryToChannel($message->id, $channelCascade->channelName);

        if (!$lastDelivery) {
            $newDelivery = $this->notificationDeliveryService->create(new CreateNotificationDeliveryDto(
                messageId: $message->id,
                channelName: $channelCascade->channelName,
                content: $message->content[$channelCascade->channelName] ?? [],
                extraData: NotificationMessageExtraData::merge(
                    $message->extra_data,
                    ['uuid' => $message->uuid ?? Type::UNDEFINED],
                ),
                sendAfter: $this->notificationCascadeService->calculateNearestTimeToSend(
                    silentTimePeriods: $message->template->cascade->silent_time_periods,
                    userTimezone: $message->recipient->timezone,
                ),
                timeout: $channelCascade->timeout,
            ));
            return;
        }

        if ($lastDelivery->is_successful) {
            return;
        }

        if (
            $lastDelivery->is_failed
            && !empty($channelCascade->onFail)
        ) {
            $onFailStatuses = $this->processChannelCascade($channelCascade->onFail, $message);
            return;
        }
    }

    private function calculateMessageStatus(NotificationMessage $message): NotificationMessageStatusEnum
    {
        $deliveryStatuses = $message->deliveries->mapWithKeys(fn(NotificationDelivery $delivery) => [$delivery->channel_name => $delivery->status]);

        // at least one intermediate status => all message is sending.. below ALL statuses are final now.
        if ($deliveryStatuses->filter(fn($status) => !$status->isFinal())->count() > 0) {
            return NotificationMessageStatusEnum::SENDING;
        }

        // hide skipped statuses
        $deliveryStatuses = $deliveryStatuses->filter(fn($status) => $status !== NotificationDeliveryStatusEnum::SKIPPED);

        // we analyze only significant statuses (but if no significant deliveries - we analyze ignored ones, to NOT fail message on "sent only to DB" case))
        // (significant = NOT ignored channels (if we have at least one sending to not-ignored channels - we take only them into account))
        $lastSignificantDeliveryStatuses = $deliveryStatuses
            ->filter(fn($status, $channelName) =>!$this->notificationChannelService->isChannelIgnoredForMessageFinalStatus($channelName));
        $analyzedDeliveryStatuses = $lastSignificantDeliveryStatuses->count() > 0 ? $lastSignificantDeliveryStatuses : $deliveryStatuses;

        $noDeliveries = $analyzedDeliveryStatuses->count() === 0;
        $haveSuccessfulStatuses = $analyzedDeliveryStatuses->filter(fn($status) => $status->isSuccessful())->count() > 0;

        return match (true) {
            $haveSuccessfulStatuses => NotificationMessageStatusEnum::SUCCESSFUL,
            $noDeliveries => NotificationMessageStatusEnum::SKIPPED,
            default => NotificationMessageStatusEnum::FAILED
        };
    }
}
