<?php

declare(strict_types=1);

namespace App\Orchid\Screens\NotificationTemplate;

use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\Models\NotificationTemplate;
use App\Modules\Notification\V1\Services\NotificationTemplate\NotificationTemplateService;
use App\Orchid\Layouts\NotificationTemplate\NotificationTemplateListLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;

class NotificationTemplateListScreen extends Screen
{
    public function query(): iterable
    {
        $filter = collect(request()->input('filter'));
        $activeFilter = match (request()->input('filter.active')) {
            'active' => true,
            'inactive' => false,
            default => null,
        };

        // DIRTY HACK - remove active filter from request to avoid applying it in filters() using allowedFilters
        request()->merge([
            'filter' => collect(request()->input('filter'))
                ->except('active')
                ->toArray(),
        ]);

        $notificationTemplates = NotificationTemplate::query()
            ->with(['cascade'])
            ->filters()
            ->when(!is_null($activeFilter), fn($query) => $query->where('active', $activeFilter))
            ->defaultSort('id', 'desc')
            ->paginate();

        // DIRTY HACK - restore original filter to make filters work in UI
        request()->merge([
            'filter' => $filter->toArray(),
        ]);

        return [
            'notification_templates' => $notificationTemplates,
        ];
    }

    public function layout(): iterable
    {
        return [
            NotificationTemplateListLayout::class,
        ];
    }

    public function name(): ?string
    {
        return 'Шаблоны уведомлений';
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Add'))
                ->icon('bs.plus-circle')
                ->route('platform.notification.templates.create'),
        ];
    }

    public function remove(Request $request): void
    {
        $notificationTemplateService = app(NotificationTemplateService::class);
        $notificationTemplateService->delete((int)$request->get('id'));
        Toast::info('Шаблон удален');
    }
}
