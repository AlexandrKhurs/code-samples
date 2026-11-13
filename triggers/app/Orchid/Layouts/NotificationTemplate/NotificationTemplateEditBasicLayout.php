<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\NotificationTemplate;

use App\Modules\Notification\V1\Facades\NotificationTrigger;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Rows;

class NotificationTemplateEditBasicLayout extends Rows
{
    public function fields(): array
    {
        return [
            Input::make('notification_template.name')
                ->required()
                ->title('Name')
                ->help('Name')
                ->type('string'),
            CheckBox::make('notification_template.active')
                ->sendTrueOrFalse()
                ->title('Active'),
            Group::make([
                Select::make('notification_template.trigger_type')
                    ->required()
                    ->options(NotificationTrigger::getTriggerNotificationTypes()->mapWithKeys(fn($type) => [$type->value => $type->value])->toArray())
                    ->empty()
                    ->title('Trigger type'),
                Input::make('notification_template.unique_for_period')
                    ->required()
                    ->title('Unique for period (seconds)')
                    ->help('notification will NOT repeat within this period (even if trigger is fired again)<br>0 - no limit, will be sent every time trigger is fired')
                    ->type('number')
                    ->min(0)
                    ->value('0'),
            ]),
        ];
    }
}
