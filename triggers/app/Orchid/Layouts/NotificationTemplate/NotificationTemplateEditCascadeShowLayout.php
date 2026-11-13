<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\NotificationTemplate;

use App\Modules\Notification\V1\Models\NotificationCascade;
use App\Orchid\Layouts\Traits\RendersNotificationCascadeElements;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Layouts\Listener;
use Orchid\Screen\Repository;
use Orchid\Support\Facades\Layout;

class NotificationTemplateEditCascadeShowLayout extends Listener
{
    use RendersNotificationCascadeElements;

    protected $targets = [
        'notification_template.cascade_id'
    ];

    public function layouts(): iterable
    {
        $cascade = NotificationCascade::find($this->query->get('notification_template.cascade_id'));
        if (!$cascade) {
            return [];
        }

        return [
            Layout::rows([
                Group::make([
                    Label::make('cascade')
                        ->title('Cascade')
                        ->value(new HtmlString(
                            $this->renderChannelCascades($cascade->channel_cascades)
                        )),
                    Label::make('silent_time_periods')
                        ->title('Silent time periods')
                        ->value(new HtmlString(
                            $this->renderSilentTimePeriods($cascade->silent_time_periods)
                        )),
                ])
            ])
        ];
    }

    public function handle(Repository $repository, Request $request): Repository
    {
        $cascadeId = $request->input('notification_template.cascade_id') ?? 0;
        $repository->set('notification_template.cascade_id', $cascadeId);
        return $repository;
    }
}
