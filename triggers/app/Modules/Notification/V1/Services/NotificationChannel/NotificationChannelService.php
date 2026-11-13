<?php

namespace App\Modules\Notification\V1\Services\NotificationChannel;

use App\Modules\Notification\V1\Enums\NotificationEditableContentTypeEnum;
use App\Modules\Notification\V1\Notifications\Channels\ChannelInterface;
use App\Modules\Notification\V1\Notifications\Channels\DatabaseChannel;
use App\Modules\Notification\V1\Notifications\Channels\FlashCallChannel;
use App\Modules\Notification\V1\Notifications\Channels\MailChannel;
use App\Modules\Notification\V1\Notifications\Channels\PushChannel;
use App\Modules\Notification\V1\Notifications\Channels\SmsChannel;
use App\Modules\Notification\V1\Notifications\Channels\TelegramChannel;

class NotificationChannelService
{
    public function __construct(
        private readonly array $availableChannelClasses,
        private readonly array $channelsIgnoredForMessageFinalStatus,
    ) {
    }

    // TODO: refactor needed: move this mapping somewhere... maybe to Channel implementations?
    public function getAllowedContent(ChannelInterface|string|null $channel): array
    {
        return match ($this->getChannelName($channel)) {
            DatabaseChannel::NAME => [
                'title' => NotificationEditableContentTypeEnum::STRING,
                'text' => NotificationEditableContentTypeEnum::TEXT,
            ],
            PushChannel::NAME => [
                'title' => NotificationEditableContentTypeEnum::STRING,
                'body' => NotificationEditableContentTypeEnum::TEXT,
            ],
            TelegramChannel::NAME => [
                'message' => NotificationEditableContentTypeEnum::TEXT,
            ],
            SmsChannel::NAME => [
                'message' => NotificationEditableContentTypeEnum::TEXT,
            ],
            MailChannel::NAME => [
                'body' => NotificationEditableContentTypeEnum::TEXT,
            ],
            FlashCallChannel::NAME => [],
            default => [],
        };
    }

    public function getChannelClass(ChannelInterface|string|null $channel): string|null
    {
        $channelName = $this->getChannelName($channel);
        return $channelName ? $this->getChannelClasses()[$channelName] : null;
    }

    public function getChannelName(ChannelInterface|string|null $channel): string|null
    {
        if (is_null($channel)) {
            return null;
        }

        if (is_string($channel) && !empty($this->getChannelNames()[$channel])) {
            return $this->getChannelNames()[$channel];
        }

        if (is_string($channel) && in_array($channel, $this->getChannelNames())) {
            return $channel;
        }

        if ($channel instanceof ChannelInterface) {
            return get_class($channel)::NAME;
        }

        return null;
    }

    public function getChannelClasses(): array
    {
        return collect($this->availableChannelClasses)
            ->mapWithKeys(fn(string $class) => [$class::NAME => $class])
            ->toArray();
    }

    public function getChannelNames(): array
    {
        return collect($this->availableChannelClasses)
            ->mapWithKeys(fn(string $class) => [$class => $class::NAME])
            ->toArray();
    }

    public function isChannelIgnoredForMessageFinalStatus(string $channelName): bool
    {
        return in_array($this->getChannelClass($channelName), $this->channelsIgnoredForMessageFinalStatus);
    }
}
