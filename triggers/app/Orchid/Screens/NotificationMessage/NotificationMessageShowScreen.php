<?php

declare(strict_types=1);

namespace App\Orchid\Screens\NotificationMessage;

use App\Modules\Notification\V1\Models\NotificationMessage;
use App\Orchid\Layouts\NotificationDelivery\NotificationDeliveryListLayout;
use App\Orchid\Layouts\NotificationMessage\NotificationMessageContentLayout;
use App\Orchid\Layouts\NotificationMessage\NotificationMessageShowLayout;
use Orchid\Screen\Screen;

class NotificationMessageShowScreen extends Screen
{
    public function query(NotificationMessage $notificationMessage): iterable
    {
        $this->setName($notificationMessage);

        $notificationMessage->load([
            'deliveries' => fn ($query) => $query->orderBy('id'),
        ]);

        return [
            'notification_message' => $notificationMessage,
        ];
    }

    public function layout(): iterable
    {
        return [
            NotificationDeliveryListLayout::class,
            NotificationMessageShowLayout::class,
            NotificationMessageContentLayout::class,
        ];
    }

    private function setName(NotificationMessage $notificationMessage): void
    {
        $this->name = sprintf(
            'Сообщение #%d (%s, to #%d %s)',
            $notificationMessage->id,
            $notificationMessage->trigger_type->value,
            $notificationMessage->recipient_id,
            $notificationMessage->recipient?->name
        );
    }
}
