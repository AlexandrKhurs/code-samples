<?php

declare(strict_types=1);

namespace App\Orchid\Screens\NotificationMessage;

use App\Modules\Notification\V1\Models\NotificationMessage;
use App\Orchid\Layouts\NotificationMessage\NotificationMessageListLayout;
use Orchid\Screen\Screen;

class NotificationMessageListScreen extends Screen
{
    public function query(): iterable
    {
        return [
            'notification_messages' => NotificationMessage::query()
                ->filters()
                ->orderBy('id', 'desc')
                ->paginate(),
        ];
    }

    public function layout(): iterable
    {
        return [
            NotificationMessageListLayout::class,
        ];
    }

    public function name(): string
    {
        return 'Логи отправки уведомлений';
    }
}
