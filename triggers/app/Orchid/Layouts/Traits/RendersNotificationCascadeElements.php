<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Traits;

use App\Modules\Common\V1\ValueObjects\TimeRange;
use App\Modules\Notification\V1\ValueObjects\NotificationChannelCascade;

trait RendersNotificationCascadeElements
{
    protected function renderChannelCascades(array $cascadeItems): string
    {
        $res = collect($cascadeItems)
            ->map(
                fn(NotificationChannelCascade $channelCascade) => $this->renderChannelCascade($channelCascade)
            )
            ->implode('');
        return "<ul>$res</ul>";
    }

    protected function renderChannelCascade(NotificationChannelCascade $channelCascade, $depth = 0): string
    {
        $res =
            '<li>' .
                $channelCascade->channelName .
                ($channelCascade->onFail ? $this->renderChannelCascade($channelCascade->onFail, $depth + 1) : '') .
            '</li>';
        if ($depth > 0) {
            $res = "<ul>$res</ul>";
        }
        return $res;
    }

    protected function renderSilentTimePeriods(array $silentTimePeriods): string
    {
        return collect($silentTimePeriods)->map(
            fn(TimeRange $timeRange) => $timeRange->toString(timeFormat: 'H:i:s')
        )->implode('<br>');
    }
}
