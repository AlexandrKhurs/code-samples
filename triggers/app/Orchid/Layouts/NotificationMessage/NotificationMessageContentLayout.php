<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\NotificationMessage;

use Orchid\Screen\Fields\Label;
use Orchid\Screen\Layouts\Rows;

class NotificationMessageContentLayout extends Rows
{
    public $title = 'Контент:';

    public function fields(): array
    {
        $notificationMessage = $this->query->get('notification_message');
        $res = [];

        foreach ($notificationMessage->content as $channelName => $channelContent) {
            $res[] = Label::make()
                ->title($channelName . ':');

            foreach ($channelContent as $fieldName => $fieldValue) {
                $res[] = Label::make()
                    ->value($fieldValue);
            }
        }
        return $res;
    }
}
