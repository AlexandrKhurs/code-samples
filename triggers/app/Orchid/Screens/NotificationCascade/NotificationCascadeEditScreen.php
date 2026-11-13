<?php

declare(strict_types=1);

namespace App\Orchid\Screens\NotificationCascade;

use App\Modules\Common\V1\Helpers\Type;
use App\Modules\Notification\V1\Helpers\NotificationChannelCascadeHelper;
use App\Modules\Notification\V1\Models\NotificationCascade;
use App\Modules\Notification\V1\Services\NotificationCascade\Dto\UpdateNotificationCascadeDto;
use App\Modules\Notification\V1\Services\NotificationCascade\NotificationCascadeService;
use App\Modules\Notification\V1\ValueObjects\NotificationChannelCascade;
use App\Orchid\Layouts\NotificationCascade\NotificationCascadeEditLayout;
use App\Orchid\Requests\NotificationCascade\UpdateNotificationCascadeRequest;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;

class NotificationCascadeEditScreen extends Screen
{
    public function query(NotificationCascade $notificationCascade): iterable
    {
        $cascadeArray = $notificationCascade->toArray();
        $cascadeArray['channel_cascades'] =
            collect($cascadeArray['channel_cascades'])
                ->map(fn(NotificationChannelCascade $item) => $item->toArray())
                ->toArray();
        return [
            'notification_cascade' => $cascadeArray,
        ];
    }

    public function layout(): iterable
    {
        return [
            NotificationCascadeEditLayout::class,
        ];
    }

    public function name(): ?string
    {
        return 'Создание каскада уведомлений';
    }

    public function commandBar(): iterable
    {
        return [
            Button::make(__('Save'))
                ->icon('bs.check-circle')
                ->method('save'),
        ];
    }

    public function save(NotificationCascade $notificationCascade, UpdateNotificationCascadeRequest $request)
    {
        $notificationCascadeService = app(NotificationCascadeService::class);
        $notificationCascade = $notificationCascadeService->update(new UpdateNotificationCascadeDto(
            id: $notificationCascade->id,
            name: $request->validated('notification_cascade.name'),
            silentTimePeriods:
                is_array($request->validated('notification_cascade.silent_time_periods'))
                ? $request->validated('notification_cascade.silent_time_periods')
                : [], // allow empty silent time periods
            channelCascades: $request->validated('notification_cascade.channel_cascades'),
        ));
        Toast::info('Каскад обновлен');
        return redirect()->route('platform.notification.cascades.edit', ['notificationCascade' => $notificationCascade->id]);
    }
}
