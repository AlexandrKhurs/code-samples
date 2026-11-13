<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\NotificationCascade;

use App\Modules\Notification\V1\Services\NotificationChannel\NotificationChannelService;
use App\Modules\Notification\V1\Services\NotificationCascade\NotificationCascadeService;
use App\Orchid\Fields\NotificationCascadeChannelCascadesArray;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Layouts\Rows;

class NotificationCascadeEditLayout extends Rows
{
    public function fields(): array
    {
        return [
            Input::make('notification_cascade.name')
                ->required()
                ->title('Name'),

            Matrix::make('notification_cascade.silent_time_periods')
                ->title('Периоды тишины')
                ->help(
                    '<b>нюанс:</b> интервал считается валидным, если начало меньше конца, т.е. 23:00..07:00 - невалиден,<br>'
                    . 'если надо перейти через 00:00 - надо указывать 2 интервала: 23:00:00..23:59:59 и 00:00:00..00:07:00'
                )
                ->asArray()
                ->columns([
                    'From' => 'from',
                    'To' => 'to',
                ])
                ->fields([
                    'from' => Input::make()
                        ->required()
                        ->type('time')
                        ->step(1)
                        ->popover('HH:MM:SS'),
                    'to' => Input::make()
                        ->required()
                        ->type('time')
                        ->step(1)
                        ->popover('HH:MM:SS'),
                ]),

            NotificationCascadeChannelCascadesArray::make('notification_cascade.channel_cascades')
                ->title('Каскады уведомлений')
                ->channels(app(NotificationChannelService::class)->getChannelNames()),
        ];
    }
}
