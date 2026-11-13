<?php

namespace App\Modules\Notification\V1\ValueObjects;

use App\Modules\Notification\V1\Enums\NotificationTriggerGroupEnum;
use App\Modules\Notification\V1\Notifications\Channels\DatabaseChannel;
use App\Modules\Notification\V1\Notifications\Channels\MailChannel;
use App\Modules\Notification\V1\Notifications\Channels\PushChannel;
use App\Modules\Notification\V1\Notifications\Channels\SmsChannel;
use App\Modules\Notification\V1\Notifications\Channels\TelegramChannel;

class NotificationGroupSettings
{
    private const DEFAULT = [
        NotificationTriggerGroupEnum::CRITICAL->value => [
            DatabaseChannel::NAME => true,
            PushChannel::NAME => true,
            TelegramChannel::NAME => true,
            SmsChannel::NAME => true,
            MailChannel::NAME => true,
        ],
        NotificationTriggerGroupEnum::MY_ACTIVITIES->value => [
            DatabaseChannel::NAME => true,
            PushChannel::NAME => true,
            TelegramChannel::NAME => true,
            SmsChannel::NAME => true,
            MailChannel::NAME => true,
        ],
        NotificationTriggerGroupEnum::NEWS->value => [
            DatabaseChannel::NAME => true,
            PushChannel::NAME => true,
            TelegramChannel::NAME => true,
            SmsChannel::NAME => true,
            MailChannel::NAME => true,
        ],
    ];

    public const RULE_REQUIRED = 'required';
    public const RULE_REQUIRED_GROUP = 'required_group';

    public const RULES = [
        NotificationTriggerGroupEnum::CRITICAL->value => [
            DatabaseChannel::NAME => [self::RULE_REQUIRED], // must be enabled
            PushChannel::NAME => [self::RULE_REQUIRED_GROUP], // at least on of groups must be enabled
            TelegramChannel::NAME => [self::RULE_REQUIRED_GROUP],
            SmsChannel::NAME => [self::RULE_REQUIRED_GROUP],
            MailChannel::NAME => [],
        ],
        NotificationTriggerGroupEnum::MY_ACTIVITIES->value => [
            DatabaseChannel::NAME => [self::RULE_REQUIRED],
            PushChannel::NAME => [],
            TelegramChannel::NAME => [],
            SmsChannel::NAME => [],
            MailChannel::NAME => [],
        ],
        NotificationTriggerGroupEnum::NEWS->value => [
            DatabaseChannel::NAME => [self::RULE_REQUIRED],
            PushChannel::NAME => [],
            TelegramChannel::NAME => [],
            SmsChannel::NAME => [],
            MailChannel::NAME => [],
        ],
    ];

    private readonly array $data;

    public function __construct(?array $data)
    {
        $this->data = $this->mergeData(self::DEFAULT, $data ?? []);

        self::validateRules($this->data, self::RULES);
    }

    public static function fromArray(?array $data): self
    {
        return new self($data);
    }

    public static function default(): self
    {
        return new self(self::DEFAULT);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function merge(array $replacements): self
    {
        return new self($this->mergeData($this->data, $replacements));
    }

    public function isEnabled(string $groupName, string $channelName): bool
    {
        return $this->data[$groupName][$channelName] ?? false;
    }

    private function mergeData(array $data, array $replacements): array
    {
        $result = [];
        foreach ($data as $groupName => $channels) {
            foreach ($channels as $channelName => $dataValue) {
                $result[$groupName][$channelName] = $replacements[$groupName][$channelName] ?? $dataValue;
            }
        }

        return $result;
    }

    public static function validateRules(array $data, array $rules): void
    {
        foreach ($rules as $triggerGroupValue => $channels) {
            $requiredGroupChannels = []; // channels with required_group rule
            $isGroupEnabled = false; // at least one channel is actually enabled

            foreach ($channels as $channelName => $rules) {
                $dataValue = (bool) ($data[$triggerGroupValue][$channelName] ?? false);

                if (in_array(self::RULE_REQUIRED, $rules) && !$dataValue) {
                    throw new \InvalidArgumentException("Channel $channelName is required in group $triggerGroupValue");
                }

                if (in_array(self::RULE_REQUIRED_GROUP, $rules)) {
                    $requiredGroupChannels[] = $channelName;
                    $isGroupEnabled = $isGroupEnabled || $dataValue;
                }
            }

            if (!empty($requiredGroupChannels) && !$isGroupEnabled) {
                $requiredGroupChannelsList = implode(', ', $requiredGroupChannels);
                throw new \InvalidArgumentException("At least one of [$requiredGroupChannelsList] must be enabled in group $triggerGroupValue");
            }
        }
    }
}
