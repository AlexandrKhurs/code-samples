<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\NotificationCascade;

use App\Modules\Notification\V1\Models\NotificationCascade;
use App\Orchid\Layouts\Traits\RendersNotificationCascadeElements;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class NotificationCascadeListLayout extends Table
{
    use RendersNotificationCascadeElements;

    public $target = 'notification_cascades';

    public function columns(): array
    {
        return [
            TD::make('name', 'Name')
                ->render(fn(NotificationCascade $notificationCascade) => $notificationCascade->name),
            TD::make('silent_time_periods', 'Silent time')
                ->render(fn(NotificationCascade $notificationCascade) =>
                    $this->renderSilentTimePeriods($notificationCascade->silent_time_periods)),
            TD::make('channel_cascades', 'Channel cascades')
                ->render(fn(NotificationCascade $notificationCascade) =>
                    $this->renderChannelCascades($notificationCascade->channel_cascades)),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn (NotificationCascade $notificationCascade) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make(__('Edit'))
                            ->route('platform.notification.cascades.edit', ['notificationCascade' => $notificationCascade->id])
                            ->icon('bs.pencil'),

                        Button::make(__('Delete'))
                            ->icon('bs.trash3')
                            ->confirm('Точно удалить?')
                            ->method('remove', [
                                'id' => $notificationCascade->id,
                            ]),
                    ])),
        ];
    }
}
