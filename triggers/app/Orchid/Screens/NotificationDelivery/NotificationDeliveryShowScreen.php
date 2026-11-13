<?php

declare(strict_types=1);

namespace App\Orchid\Screens\NotificationDelivery;

use App\Modules\Notification\V1\Models\NotificationDelivery;
use App\Orchid\Layouts\NotificationDelivery\NotificationDeliveryShowContentLayout;
use App\Orchid\Layouts\NotificationDelivery\NotificationDeliveryShowExtraDataLayout;
use App\Orchid\Layouts\NotificationDelivery\NotificationDeliveryShowLayout;
use App\Orchid\Layouts\NotificationDelivery\NotificationDeliveryShowLogLayout;
use Orchid\Screen\Screen;

class NotificationDeliveryShowScreen extends Screen
{
    public function query(NotificationDelivery $notificationDelivery): iterable
    {
        $this->setName($notificationDelivery);

        return [
            'notification_delivery' => $notificationDelivery,
        ];
    }

    public function layout(): iterable
    {
        return [
            NotificationDeliveryShowLogLayout::class,
            NotificationDeliveryShowLayout::class,
            NotificationDeliveryShowContentLayout::class,
            NotificationDeliveryShowExtraDataLayout::class,
        ];
    }

    private function setName(NotificationDelivery $notificationDelivery): void
    {
        $this->name = sprintf(
            'Доставка #%d (%s)',
            $notificationDelivery->id,
            $notificationDelivery->channel_name
        );
    }
}
