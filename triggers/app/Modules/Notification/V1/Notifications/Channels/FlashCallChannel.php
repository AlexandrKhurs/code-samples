<?php

namespace App\Modules\Notification\V1\Notifications\Channels;

use App\Modules\Notification\V1\Notifications\Notifiable\Notifiable;
use App\Modules\Notification\V1\Notifications\Notification;
use App\Modules\Notification\V1\Services\FlashCall\FlashCallServiceInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class FlashCallChannel implements ChannelInterface
{
    public const NAME = 'flash_call';

    public function __construct(
        private readonly FlashCallServiceInterface $flashCallService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function send(Notifiable $notifiable, Notification $notification): void
    {
        try {
            $this->flashCallService->send($notifiable, $notification);
        } catch (Throwable $e) {
            $this->logger->warning($e->getMessage(), [
                'notifiable' => $notifiable,
                'notifier' => $this->flashCallService::class
            ]);
        }
    }
}
