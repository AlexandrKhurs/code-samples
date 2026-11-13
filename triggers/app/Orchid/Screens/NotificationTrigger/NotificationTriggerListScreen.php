<?php

declare(strict_types=1);

namespace App\Orchid\Screens\NotificationTrigger;

use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\Models\NotificationTemplate;
use App\Modules\Notification\V1\NotificationTriggers\Trigger;
use App\Orchid\Layouts\NotificationTrigger\NotificationTriggerListLayout;
use Orchid\Screen\Screen;

class NotificationTriggerListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'notification_triggers' => NotificationTrigger::triggerClasses()
                ->map(fn(string $triggerClass) => [
                    'trigger' => $triggerClass::empty(),
                    'available_recipients' => collect(NotificationTrigger::getAllowedRecipients($triggerClass::empty())),
                    'templates_configured' => NotificationTemplate::where('trigger_type', $triggerClass::empty()->type())->get()
                ])
                ->sortBy(fn(array $data) => $data['trigger']?->type()?->value)
                ->toArray(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Триггеры уведомлений';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Available notification triggers';
    }

    public function permission(): ?iterable
    {
        return [
            'platform.systems.users',
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return string[]|\Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        return [
            NotificationTriggerListLayout::class,
        ];
    }
}
