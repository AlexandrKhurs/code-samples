<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\NotificationTemplate;

use Orchid\Screen\Layouts\Rows;
use Orchid\Screen\Fields\Label;

class NotificationTemplateEditValidationLayout extends Rows
{
    public function fields(): array
    {
        $validationError = $this->query->get('validation_errors');

        if (!$validationError) {
            return [
                Label::make('success')
                    ->value('Template seems to be valid')
                    ->class('alert alert-success'),
            ];
        }

        return [
            Label::make('warning')
                ->value($validationError)
                ->class('alert alert-warning'),
        ];
    }
}
