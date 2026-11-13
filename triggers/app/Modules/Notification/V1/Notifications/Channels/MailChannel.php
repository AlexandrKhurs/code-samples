<?php

namespace App\Modules\Notification\V1\Notifications\Channels;

use App\Modules\Notification\V1\Notifications\Channels\Traits\UpdatesNotificationDeliveryStatusFromSendingResult;
use App\Modules\Notification\V1\Notifications\Notifiable\Notifiable;
use App\Modules\Notification\V1\Notifications\Notification;
use App\Modules\Notification\V1\Services\Mail\MailServiceInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class MailChannel implements ChannelInterface
{
    use UpdatesNotificationDeliveryStatusFromSendingResult;

    public const NAME = 'mail';

    public function __construct(
        private readonly MailServiceInterface $mailService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function send(Notifiable $notifiable, Notification $notification): void
    {
        try {
            $result = $this->mailService->send($notifiable, $notification);
            $this->updateNotificationDeliveryStatus($notification, $result);
        } catch (Throwable $e) {
            $this->logger->warning($e->getMessage(), [
                'notifiable' => $notifiable,
                'notifier' => $this->mailService::class
            ]);
            $this->updateNotificationDeliveryStatus($notification, $e);
        }
    }
}
