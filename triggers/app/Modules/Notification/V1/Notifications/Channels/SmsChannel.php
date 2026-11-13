<?php

namespace App\Modules\Notification\V1\Notifications\Channels;

use App\Modules\Notification\V1\Notifications\Channels\Traits\UpdatesNotificationDeliveryStatusFromSendingResult;
use App\Modules\Notification\V1\Notifications\Notifiable\Notifiable;
use App\Modules\Notification\V1\Notifications\Notification;
use App\Modules\Notification\V1\Services\Sms\SmsServiceInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class SmsChannel implements ChannelInterface
{
    use UpdatesNotificationDeliveryStatusFromSendingResult;

    public const NAME = 'sms';

    public function __construct(
        private readonly SmsServiceInterface $smsService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function send(Notifiable $notifiable, Notification $notification): void
    {
        try {
            $result = $this->smsService->send($notifiable, $notification);
            $this->updateNotificationDeliveryStatus($notification, $result);
        } catch (Throwable $e) {
            $this->logger->warning($e->getMessage(), [
                'notifiable' => $notifiable,
                'notifier' => $this->smsService::class
            ]);
            $this->updateNotificationDeliveryStatus($notification, $e);
        }
    }
}
