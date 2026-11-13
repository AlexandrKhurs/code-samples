<?php

namespace App\Modules\Notification\V1\Notifications\Channels;

use App\Modules\Notification\V1\Notifications\Channels\Traits\UpdatesNotificationDeliveryStatusFromSendingResult;
use App\Modules\Notification\V1\Notifications\Notifiable\Notifiable;
use App\Modules\Notification\V1\Notifications\Notification;
use App\Modules\Notification\V1\Services\Telegram\TelegramServiceInterface;
use Psr\Log\LoggerInterface;
use Throwable;

// TODO: refactor: decouple this from NotificationDelivery
// (idk how yet... maybe fire events on error/success or smth, but NOT update NotificationDelivery directly)
// (here and in other channels)
class TelegramChannel implements ChannelInterface
{
    use UpdatesNotificationDeliveryStatusFromSendingResult;

    public const NAME = 'telegram';

    public function __construct(
        private readonly TelegramServiceInterface $sendService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function send(Notifiable $notifiable, Notification $notification): void
    {
        try {
            $result = $this->sendService->send($notifiable, $notification);
            $this->updateNotificationDeliveryStatus($notification, $result);
        } catch (Throwable $e) {
            $this->logger->warning($e->getMessage(), [
                'notifiable' => $notifiable,
                'notifier' => $this->sendService::class,
            ]);
            $this->updateNotificationDeliveryStatus($notification, $e);
            if ($notification->throwWhenError) {
                throw $e;
            }
        }
    }
}
