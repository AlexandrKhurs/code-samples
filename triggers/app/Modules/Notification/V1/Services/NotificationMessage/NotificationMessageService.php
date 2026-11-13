<?php

namespace App\Modules\Notification\V1\Services\NotificationMessage;

use App\Modules\Common\V1\Helpers\FilterHelper;
use App\Modules\Notification\V1\Events\NotificationMessageCreatedEvent;
use App\Modules\Notification\V1\Models\NotificationMessage;
use App\Modules\Notification\V1\Services\NotificationMessage\Dto\CreateNotificationMessageDto;
use App\Modules\Notification\V1\Services\NotificationMessage\Dto\UpdateNotificationMessageDto;
use Ramsey\Uuid\Uuid;

class NotificationMessageService
{
    public function create(CreateNotificationMessageDto $dto): NotificationMessage
    {
        $message = NotificationMessage::create(FilterHelper::getDefinedData([
            'trigger_type' => $dto->triggerType,
            'template_id' => $dto->templateId,
            'recipient_id' => $dto->recipientId,
            'cascade_id' => $dto->cascadeId,
            'content' => $dto->content,
            'extra_data' => $dto->extraData,
            'status' => $dto->status,
            'trigger_reconstruction_data' => $dto->triggerReconstructionData,
            'uuid' => $this->generateUuid(),
        ]));

        event(new NotificationMessageCreatedEvent($message->id));

        return $message;
    }

    public function update(int $messageId, UpdateNotificationMessageDto $dto): void
    {
        NotificationMessage::query()
            ->where('id', $messageId)
            ->update(FilterHelper::getDefinedData([
                'status' => $dto->status,
                'content' => $dto->content,
                'extra_data' => $dto->extraData,
            ]));
    }

    private function generateUuid(): string
    {
        // paranoid check for uniqueness
        do {
            $uuid = Uuid::uuid4()->toString();
        } while (NotificationMessage::where('uuid', $uuid)->exists());

        return $uuid;
    }

    /** check if no similar message was created in the last $message->unique_for_period seconds */
    public function isUniqueForPeriod(NotificationMessage $message): bool
    {
        return
            empty($message->template?->unique_for_period)
            || (
                NotificationMessage::query()
                    ->where('trigger_type', $message->trigger_type)
                    ->where('recipient_id', $message->recipient_id)
                    ->where('template_id', $message->template_id)
                    ->where('created_at', '>=', $message->created_at->clone()->subSeconds($message->template?->unique_for_period))
                    ->where('id', '<', $message->id) // only previous messages
                    ->doesntExist()
            );
    }
}
