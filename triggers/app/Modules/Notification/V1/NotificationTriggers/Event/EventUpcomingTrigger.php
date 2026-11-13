<?php

namespace App\Modules\Notification\V1\NotificationTriggers\Event;

use App\Modules\Event\V1\Enums\UpcomingEventNotificationStageEnum;
use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\Notification\V1\NotificationTriggers\BaseTriggers\BaseEventTrigger;
use App\Modules\Tournament\V1\Services\Tournament\TournamentService;
use Carbon\CarbonInterval;

class EventUpcomingTrigger extends BaseEventTrigger
{
    public ?UpcomingEventNotificationStageEnum $notificationStage = null;
    // the start time the reminder was fired for: the reminder is outdated once the start moves
    private ?string $timeStart;

    public function __construct(
        ?int $eventId,
        UpcomingEventNotificationStageEnum|string|null $notificationStage,
        ?string $timeStart = null,
    ) {
        parent::__construct($eventId);
        $this->notificationStage = is_string($notificationStage)
            ? UpcomingEventNotificationStageEnum::tryFrom($notificationStage)
            : $notificationStage;
        $this->timeStart = $timeStart ?? $this->event?->time_start?->toDateTimeString();
    }

    public function type(): NotificationTypeEnum
    {
        return NotificationTypeEnum::EVENTS_UPCOMING;
    }

    public function filters(): array
    {
        $optionDescriptions = $this->notificationConfigHumanReadableValues();

        return [
            ...parent::filters(),
            'notification_stage' => [
                'earlier' => 'early' . $optionDescriptions['earlier'],
                'notification_1' => '#1' . $optionDescriptions['notification_1'],
                'notification_2' => '#2' . $optionDescriptions['notification_2'],
                'notification_3' => '#3' . $optionDescriptions['notification_3'],
                'notification_4' => '#4' . $optionDescriptions['notification_4'],
            ],
        ];
    }

    // TODO: think of better filters structure (e.g. VO) for complex logic (see Trigger::applyFilters)
    // and filtering helpers or smth (to not repeat as much).
    public function applyFilters(array $filters): bool
    {
        if (!parent::applyFilters($filters)) {
            return false;
        }

        foreach ($filters as $filter) {
            [$filterKey, $filterOperator, $filterValues] = $filter;
            if ($filterOperator !== 'in') {
                throw new \InvalidArgumentException('Unsupported filter operator: ' . $filterOperator);
            }
            if (empty($filterValues)) {
                continue;
            }

            $notificationFilterValue = null;
            $knownFilter = true;
            switch ($filterKey) {
                case 'notification_stage': // ['notification_stage', 'in', ['notification_1', 'notification_2', 'notification_3']]
                    $notificationFilterValue = $this->notificationStage?->value;
                    break;

                // ... other filters ...

                default:
                    $knownFilter = false;
                    break;
            }

            if ($knownFilter && !in_array($notificationFilterValue, $filterValues ?? [])) {
                return false;
            }
        }

        // all passed
        return true;
    }

    protected function notificationConfigHumanReadableValues(): array
    {
        $configValues = [
            'earlier' => config('services.event.earlier_notification_send_at_hour'),
            'notification_1' => config('services.event.upcoming_notification_1'),
            'notification_2' => config('services.event.upcoming_notification_2'),
            'notification_3' => config('services.event.upcoming_notification_3'),
            'notification_4' => config('services.event.upcoming_notification_4'),
        ];

        $valueConverters = [
            'earlier' => fn($value) => $value ? "at $value:00" : null,
            'notification_1' => fn($value) => $value ? CarbonInterval::seconds($value)->cascade()->locale('en')->forHumans() . ' before' : null,
            'notification_2' => fn($value) => $value ? CarbonInterval::seconds($value)->cascade()->locale('en')->forHumans() . ' before' : null,
            'notification_3' => fn($value) => $value ? CarbonInterval::seconds($value)->cascade()->locale('en')->forHumans() . ' before' : null,
            'notification_4' => fn($value) => $value ? CarbonInterval::seconds($value)->cascade()->locale('en')->forHumans() . ' before' : null,
        ];

        $descriptionTemplate = ' - %s';

        return collect($configValues)
            ->mapWithKeys(fn($value, $key) => [
                $key => $valueConverters[$key]($value) ? sprintf($descriptionTemplate, $valueConverters[$key]($value)) : ''
                ])
            ->toArray();
    }

    public function toReconstructionData(): array
    {
        return [
            'eventId' => $this->event?->id,
            'notificationStage' => $this->notificationStage?->value,
            'timeStart' => $this->timeStart,
        ];
    }

    public function isStillFired(): bool
    {
        // a canceled or started event, or an event with a moved start, does not get the reminder
        $tournamentStatus = $this->event?->tournament?->status;

        return $this->event
            && is_null($this->event->canceled_at)
            && (is_null($tournamentStatus) || app(TournamentService::class)->isNotStartedStatus($tournamentStatus))
            && $this->event->time_start?->toDateTimeString() === $this->timeStart;
    }

    protected function uniqueIdData(): array
    {
        return [
            'event_id' => $this->event?->id,
            'notification_stage' => $this->notificationStage?->value,
            'time_start' => $this->timeStart,
        ];
    }

    public function uniqueIdTtl(): int
    {
        return 7 * 24 * 60 * 60; // 7 days
    }
}
