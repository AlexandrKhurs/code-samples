<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\NotificationDelivery;

use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class NotificationDeliveryShowLogLayout extends Table
{
    public $target = 'notification_delivery.log';
    public $title = 'Лог:';

    public function columns(): array
    {
        return [
            TD::make('time', 'Time')
                ->render(fn($log) => $log),
        ];
    }
}
