<?php

declare(strict_types=1);

namespace App\Orchid\Screens\NotificationCascade;

use App\Modules\Notification\V1\Models\NotificationCascade;
use App\Modules\Notification\V1\Services\NotificationCascade\NotificationCascadeService;
use App\Orchid\Layouts\NotificationCascade\NotificationCascadeListLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;

class NotificationCascadeListScreen extends Screen
{
    public function query(): iterable
    {
        return [
            'notification_cascades' => NotificationCascade::query()
                ->orderBy('id', 'desc')
                ->paginate(),
        ];
    }
    public function name(): ?string
    {
        return 'Каскады уведомлений';
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Add'))
                ->icon('bs.plus-circle')
                ->route('platform.notification.cascades.create'),
        ];
    }
    public function layout(): iterable
    {
        return [
            NotificationCascadeListLayout::class,
        ];
    }

    public function remove(Request $request): void
    {
        $notificationCascadeService = app(NotificationCascadeService::class);
        $notificationCascadeService->delete((int)$request->get('id'));
        Toast::info('Каскад удален');
    }
}
