<?php

namespace App\Modules\Notification\V1\Notifications;

use App\Modules\Common\V1\Enums\QueueChannelEnum;
use App\Modules\Notification\V1\Enums\NotificationSubjectEnum;
use App\Modules\Notification\V1\Enums\NotificationVisibleEnum;
use App\Modules\Notification\V1\Notifications\Channels\DatabaseChannel;
use App\Modules\Notification\V1\Notifications\Channels\FlashCallChannel;
use App\Modules\Notification\V1\Notifications\Channels\MailChannel;
use App\Modules\Notification\V1\Notifications\Channels\PushChannel;
use App\Modules\Notification\V1\Notifications\Channels\SmsChannel;
use App\Modules\Notification\V1\Notifications\Channels\TelegramChannel;
use App\Modules\Notification\V1\Notifications\Notifiable\NotifiableChat;
use App\Modules\Notification\V1\Notifications\Notifiable\NotifiableEmail;
use App\Modules\Notification\V1\Notifications\Notifiable\NotifiablePhone;
use App\Modules\Notification\V1\Notifications\Notifiable\NotifiableUser;
use App\Modules\Notification\V1\Services\Notification\Dto\DatabaseNotificationDto;
use App\Modules\Notification\V1\Services\Push\Dto\PushMessageDto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification as BaseNotification;

abstract class Notification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public $visible = NotificationVisibleEnum::PRIVATE;
    public $subject = NotificationSubjectEnum::APP;
    public $type;
    public $users;
    public $channels;
    public $throwWhenError = false;

    abstract public function toDatabase(): ?DatabaseNotificationDto;
    abstract public function toPush(): ?PushMessageDto;
    abstract public function toMail(): ?Mailable;
    abstract public function toSms(): ?string;
    abstract public function toFlashCall(): ?string;
    abstract public function toTelegram(): ?string;

    public function via(object $notifiable): array
    {
        return $this->channelsFilter($notifiable, []);
    }

    public function channelsFilter(object $notifiable, $channels): array
    {
        switch ($notifiable::class) {
            case NotifiableUser::class:
                $availableChannels = [
                    DatabaseChannel::class,
                    PushChannel::class,
                    MailChannel::class,
                    SmsChannel::class,
                    TelegramChannel::class,
                    FlashCallChannel::class,
                ];
                break;
            case NotifiableChat::class:
                $availableChannels = [TelegramChannel::class];
                break;
            case NotifiableEmail::class:
                $availableChannels = [MailChannel::class];
                break;
            case NotifiablePhone::class:
                $availableChannels = [
                    SmsChannel::class,
                    FlashCallChannel::class,
                ];
                break;
            default:
                $availableChannels = [];
                break;
        }

        if (is_array($this->channels)) {
            return array_intersect($channels, $availableChannels, $this->channels);
        }

        return array_intersect($channels, $availableChannels);
    }

    public function toChannels(array $channels): Notification
    {
        $this->channels = $channels;
        return $this;
    }

    public function viaQueues(): array
    {
        $notificationQueue = config('notification.queue');

        return [
            DatabaseChannel::class => $notificationQueue,
            PushChannel::class => $notificationQueue,
            TelegramChannel::class => $notificationQueue,
            SmsChannel::class => $notificationQueue,
            MailChannel::class => $notificationQueue,
            FlashCallChannel::class => QueueChannelEnum::DEFAULT->value,
        ];
    }
}
