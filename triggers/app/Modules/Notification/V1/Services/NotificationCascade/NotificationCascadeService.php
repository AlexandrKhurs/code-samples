<?php

namespace App\Modules\Notification\V1\Services\NotificationCascade;

use App\Modules\Common\V1\Helpers\FilterHelper;
use App\Modules\Common\V1\ValueObjects\TimeRange;
use App\Modules\Notification\V1\Models\NotificationCascade;
use App\Modules\Notification\V1\Services\NotificationCascade\Dto\CreateNotificationCascadeDto;
use App\Modules\Notification\V1\Services\NotificationCascade\Dto\UpdateNotificationCascadeDto;
use App\Modules\Notification\V1\ValueObjects\NotificationChannelCascade;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class NotificationCascadeService
{
    public function create(CreateNotificationCascadeDto $dto): NotificationCascade
    {
        $notificationCascade = NotificationCascade::create(FilterHelper::getDefinedData([
            'name' => $dto->name,
            'silent_time_periods' => $this->normalizeTimeRangesArrayRepresentation($dto->silentTimePeriods),
            'channel_cascades' => $this->normalizeChannelCascadesArrayRepresentation($dto->channelCascades),
        ]));

        return $notificationCascade;
    }

    public function update(UpdateNotificationCascadeDto $dto): NotificationCascade
    {
        $notificationCascade = NotificationCascade::findOrFail($dto->id);
        $notificationCascade->update(FilterHelper::getDefinedData([
            'name' => $dto->name,
            'silent_time_periods' =>
                is_array($dto->silentTimePeriods)
                ? $this->normalizeTimeRangesArrayRepresentation($dto->silentTimePeriods)
                : $dto->silentTimePeriods,
            'channel_cascades' =>
                is_array($dto->channelCascades)
                ? $this->normalizeChannelCascadesArrayRepresentation($dto->channelCascades)
                : $dto->channelCascades,
        ]));

        return $notificationCascade;
    }

    public function delete(int $id): void
    {
        NotificationCascade::where('id', $id)->delete();
    }

    public function getChannelNames(int $notificationCascadeId): Collection
    {
        $notificationCascade = NotificationCascade::find($notificationCascadeId);

        return collect($notificationCascade?->channel_cascades ?? [])
            ->map(function (NotificationChannelCascade $channelCascade) {
                $res = [];
                $thisCascadeItem = $channelCascade;
                do {
                    $res[] = $thisCascadeItem->channelName;
                    $thisCascadeItem = $thisCascadeItem->onFail;
                } while ($thisCascadeItem !== null);
                return $res;
            })
            ->flatten()
            ->unique();
    }

    // ensure it's array of TimeRange VOs (not of array representations of TimeRanges) with no null values
    private function normalizeTimeRangesArrayRepresentation(array $timeRanges): array
    {
        return array_filter(array_map(
            fn(array|TimeRange|null $timeRange) => match (true) {
                is_array($timeRange) => TimeRange::fromArray($timeRange),
                $timeRange instanceof TimeRange => $timeRange,
                default => null,
            },
            $timeRanges
        ));
    }

    // ensure it's array of NotificationChannelCascade VOs (not of array representations of Cascades) with no null values
    private function normalizeChannelCascadesArrayRepresentation(array $channelCascades): array
    {
        return array_filter(array_map(
            fn(array|NotificationChannelCascade|null $channelCascade) => match (true) {
                is_array($channelCascade) => NotificationChannelCascade::fromArray($channelCascade),
                $channelCascade instanceof NotificationChannelCascade => $channelCascade,
                default => null,
            },
            $channelCascades
        ));
    }

    /** calculate nearest time to send based on silent time periods
     * @param array[TimeRange] $silentTimePeriods array of TimeRange VOs (in user's LOCAL timezone)
     * @return TimeRange|null nearest time to send or null if no silent periods are set (in APP's timezone)
     */
    public function calculateNearestTimeToSend(array $silentTimePeriods, int $userTimezone = 0): Carbon
    {
        // TODO: maybe move all timestamps to timezoneTZ instead of those tz conversions??
        $userSendTime = now()->tz($userTimezone);
        $userNow = now()->tz($userTimezone);
        $appTimezone = config('app.timezone');

        usort(
            $silentTimePeriods,
            function (TimeRange $a, TimeRange $b) use ($userNow) {
                $aTo = $userNow->clone()->setTimeFrom($a->to)->lessThan($userNow) ? $a->to->clone()->addDay() : $a->to;
                $bTo = $userNow->clone()->setTimeFrom($b->to)->lessThan($userNow) ? $b->to->clone()->addDay() : $b->to;
                return $aTo->timestamp <=> $bTo->timestamp;
            }
        );

        foreach ($silentTimePeriods as $silentTimePeriod) {
            if ($silentTimePeriod->contains($userSendTime)) {
                $userSendTime->setTimeFrom($silentTimePeriod->to->clone())->addSecond();
            }
        }

        return $userSendTime->tz($appTimezone);
    }
}
