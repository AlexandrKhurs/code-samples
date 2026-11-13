<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\NotificationDelivery;

use App\Modules\Notification\V1\Models\NotificationDelivery;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Layouts\Rows;

class NotificationDeliveryShowContentLayout extends Rows
{
    public $title = 'Контент:';

    public function fields(): array
    {
        $notificationDelivery = $this->query->get('notification_delivery');
        return [
            ...$this->renderContent($notificationDelivery),
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
}
