<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\NotificationMessage;

use Orchid\Screen\Fields\Label;
use Orchid\Screen\Layouts\Rows;

class NotificationMessageShowLayout extends Rows
{
    public $title = 'Параметры:';

    public function fields(): array
    {
        $notificationMessage = $this->query->get('notification_message');

        return [
            Label::make('trigger_type', 'Trigger type')
                ->title('Trigger type')
                ->value($notificationMessage->trigger_type->value),

            Label::make('status', 'Status')
                ->title('Status')
                ->value($notificationMessage->status->name),
        ];
    }
}
