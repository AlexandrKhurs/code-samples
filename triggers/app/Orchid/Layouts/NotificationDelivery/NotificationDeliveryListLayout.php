<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\NotificationDelivery;

use App\Modules\Common\V1\Helpers\HtmlHelper;
use App\Modules\Notification\V1\Enums\NotificationDeliveryStatusEnum;
use App\Modules\Notification\V1\Models\NotificationDelivery;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class NotificationDeliveryListLayout extends Table
{
    public $target = 'notification_message.deliveries';
    public $title = 'Отправка в каналы:';

    public function columns(): array
    {
        return [
            TD::make('id', 'ID')
                ->sort()
                ->filter(Input::make()),
            TD::make('created_at', 'Created at')
                ->render(fn(NotificationDelivery $notificationDelivery) => $notificationDelivery->created_at?->format('Y-m-d H:i:s')),
            TD::make('updated_at', 'Updated at')
                ->render(fn(NotificationDelivery $notificationDelivery) => $notificationDelivery->updated_at?->format('Y-m-d H:i:s')),
            TD::make('channel_name', 'Channel')
                ->filter(),
            TD::make('status', 'Status')
                ->render(fn(NotificationDelivery $notificationDelivery) => $this->renderStatus($notificationDelivery->status)),

            TD::make('actions', 'Actions')
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn(NotificationDelivery $notificationDelivery) => Link::make()
                    ->route('platform.notification.deliveries.show', $notificationDelivery->id)
                    ->icon('bs.eye-fill')),
        ];
    }

    private function renderStatus(NotificationDeliveryStatusEnum $status): string
    {
        return match (true) {
            $status->isSuccessful() => HtmlHelper::badgeSuccess($status->name),
            $status === NotificationDeliveryStatusEnum::SKIPPED => HtmlHelper::badgeSecondary($status->name),
            $status->isFailed() => HtmlHelper::badgeDanger($status->name),
            default => HtmlHelper::badgePrimary($status->name),
        };
    }
}
