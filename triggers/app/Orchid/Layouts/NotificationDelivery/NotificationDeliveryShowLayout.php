<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\NotificationDelivery;

use App\Modules\Notification\V1\Models\NotificationDelivery;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Layouts\Rows;

class NotificationDeliveryShowLayout extends Rows
{
    public $title = 'Основное:';

    public function fields(): array
    {
        $notificationDelivery = $this->query->get('notification_delivery');
        return [
            Label::make('id', 'ID')
                ->title('ID'),
            Label::make('channel_name', 'Channel')
                ->title('Channel')
                ->value($notificationDelivery->channel_name),
            Label::make('send_after', 'Send after')
                ->title('Send after')
                ->value($notificationDelivery->send_after?->format('Y-m-d H:i:s')),
            Label::make('timeout', 'Timeout')
                ->title('Timeout')
                ->value($notificationDelivery->timeout),
            Label::make('status', 'Status')
                ->title('Status')
                ->value($notificationDelivery->status->name),
            Label::make('sending_started_at', 'Sending started at')
                ->title('Sending started at')
                ->value($notificationDelivery->sending_started_at?->format('Y-m-d H:i:s')),
        ];
    }

    private function renderContent(NotificationDelivery $notificationDelivery): array
    {
        $res = [];
        foreach ($notificationDelivery->content as $fieldName => $fieldValue) {
            $res[] = Label::make()
                ->title($fieldName)
                ->value($fieldValue);
        }
        return $res;
    }

    private function renderExtraData(NotificationDelivery $notificationDelivery): array
    {
        $res = [];
        foreach ($notificationDelivery->extra_data?->toArray() ?? [] as $fieldName => $fieldValue) {
            $res[] = Label::make()
                ->title($fieldName)
                ->value($fieldValue);
        }
        return $res;
    }
}
