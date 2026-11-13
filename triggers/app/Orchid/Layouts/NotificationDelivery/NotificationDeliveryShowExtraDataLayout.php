<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\NotificationDelivery;

use App\Modules\Notification\V1\Models\NotificationDelivery;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Layouts\Rows;

class NotificationDeliveryShowExtraDataLayout extends Rows
{
    public $title = 'Дополнительные данные:';

    public function fields(): array
    {
        $notificationDelivery = $this->query->get('notification_delivery');
        return [
            ...$this->renderExtraData($notificationDelivery),
        ];
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
