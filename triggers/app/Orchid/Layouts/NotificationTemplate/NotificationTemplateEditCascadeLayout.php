<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\NotificationTemplate;

use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Rows;
use App\Modules\Notification\V1\Models\NotificationCascade;

class NotificationTemplateEditCascadeLayout extends Rows
{
    public function fields(): array
    {
        return [
            Select::make('notification_template.cascade_id')
                ->title('Cascade')
                ->fromModel(NotificationCascade::class, 'name', 'id')
                ->empty()
                ->required(),
        ];
    }
}
