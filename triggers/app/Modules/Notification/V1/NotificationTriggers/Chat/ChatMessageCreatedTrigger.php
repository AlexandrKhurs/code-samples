<?php

namespace App\Modules\Notification\V1\NotificationTriggers\Chat;

use App\Modules\Chat\V1\Models\Chat;
use App\Modules\Chat\V1\Models\Message;
use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\Notification\V1\NotificationTriggers\BaseTriggers\BaseEventTrigger;
use App\Modules\Notification\V1\NotificationTriggers\Traits\TriggerHasUser;
use App\Modules\Notification\V1\ValueObjects\NotificationMessageExtraData;
use App\Modules\User\V1\Models\User;

class ChatMessageCreatedTrigger extends BaseEventTrigger
{
    use TriggerHasUser;

    protected ?Chat $chat = null;
    protected ?Message $message = null;

    public function __construct(?int $messageId)
    {
        $this->message = Message::withTrashed()->find($messageId);
        $this->chat = Chat::withTrashed()->find($this->message?->chat_id);
        $this->user = User::withTrashed()->find($this->message?->user_id);
        return parent::__construct($this->chat?->event_id);
    }

    public function type(): NotificationTypeEnum
    {
        return NotificationTypeEnum::MESSAGES_CREATE;
    }

    public function description(): ?string
    {
        return 'Новое сообщение в чате';
    }

    public function recipients(): array
    {
        return [
            ...parent::recipients(),
            'user',
            'chat.users' => fn() => $this->chat?->users,
        ];
    }

    public function recipientDescriptions(): array
    {
        return [
            ...parent::recipientDescriptions(),
            'user' => 'message author',
            'chat.users' => 'all users in this chat',
        ];
    }

    public function variables(): array
    {
        return [
            ...parent::variables(),
            'user.name' => fn() => $this->user?->name,
            'chat.message' => $this->message?->text,
        ];
    }

    public function extraData(): ?NotificationMessageExtraData
    {
        return new NotificationMessageExtraData(
            triggerType: $this->type(),
            eventId: $this->event?->id,
            eventSlug: $this->event?->slug,
            eventType: $this->event?->type,
            chatId: $this->chat?->id,
        );
    }

    protected function uniqueIdData(): array
    {
        return [
            ...parent::uniqueIdData(),
            'message_id' => $this->message?->id,
        ];
    }
}
