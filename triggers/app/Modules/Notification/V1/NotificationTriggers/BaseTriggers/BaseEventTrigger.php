<?php

namespace App\Modules\Notification\V1\NotificationTriggers\BaseTriggers;

use App\Modules\Common\V1\Helpers\EventHelper;
use App\Modules\Common\V1\Helpers\NotificationHelper;
use App\Modules\Event\V1\Enums\EventTypeEnum;
use App\Modules\Event\V1\Enums\EventUserStatusEnum;
use App\Modules\Event\V1\Models\Event;
use App\Modules\Event\V1\Models\EventUser;
use App\Modules\Notification\V1\NotificationTriggers\Trigger;
use App\Modules\Notification\V1\ValueObjects\NotificationMessageExtraData;
use Illuminate\Support\Collection;

abstract class BaseEventTrigger extends Trigger
{
    private const PLAYER_STATUSES = [
        EventUserStatusEnum::CONFIRMED,
    ];

    // a payment hold is neither a player nor an applicant
    private const APPLICANT_STATUSES = [
        EventUserStatusEnum::DEFAULT,
        EventUserStatusEnum::QUEUED,
        EventUserStatusEnum::INVITED,
    ];

    public ?Event $event = null;
    public int $timezone = 3; // event's local timezone (NOT user's)

    public function __construct(
        ?int $eventId,
    ) {
        $this->event = Event::withTrashed()->find($eventId);
        $this->timezone = $this->event?->clubLocation?->timezone ?? 3; // TODO: remove hardcoded default timezone
    }

    public function filters(): array
    {
        return [
            'event.type' => array_combine(
                collect(EventTypeEnum::cases())->pluck('value')->toArray(),
                collect(EventTypeEnum::cases())->pluck('name')->toArray()
            ),
            'event.free' => [
                'free' => 'Free',
                'paid' => 'Paid',
            ],
            'event.full' => [
                'yes' => 'full - no free spots',
                'no' => 'has free spots',
            ],
            'event.need_confirm' => [
                'yes' => 'yes - player needs to be confirmed',
                'no' => 'no - player joins immediately',
            ],
            'event.privacy' => [
                'private' => 'private (by link)',
                'public' => 'public',
            ],
        ];
    }

    // TODO: think of better filters structure (e.g. VO) for complex logic (see Trigger::applyFilters)
    // and filtering helpers or smth (to not repeat as much).
    public function applyFilters(array $filters): bool
    {
        foreach ($filters as $filter) {
            [$filterKey, $filterOperator, $filterValues] = $filter;
            if ($filterOperator !== 'in') {
                throw new \InvalidArgumentException('Unsupported filter operator: ' . $filterOperator);
            }
            if (empty($filterValues)) {
                continue;
            }

            $eventFilterValue = null;
            $knownFilter = true;
            switch ($filterKey) {
                case 'event.type': // ['event.type', 'in', [...]]
                    $eventFilterValue = $this->event?->type->value;
                    break;

                case 'event.free': // ['event.free', 'in', ['free', 'paid']]
                    $eventFilterValue = match ($this->event?->allow_join_without_payment) {
                        true => 'free',
                        false => 'paid',
                        default => null,
                    };
                    break;

                case 'event.full': // ['event.full', 'in', ['yes', 'no']]
                    $eventFilterValue = match (true) {
                        $this->event && EventHelper::getFreeSpotsCount($this->event) > 0 => 'no',
                        $this->event && EventHelper::getFreeSpotsCount($this->event) <= 0 => 'yes',
                        default => null,
                    };
                    break;

                case 'event.need_confirm': // ['event.need_confirm', 'in', ['yes', 'no']]
                    $eventFilterValue = match ($this->event?->need_confirm_event_user) {
                        true => 'yes',
                        false => 'no',
                        default => null,
                    };
                    break;

                case 'event.privacy': // ['event.privacy', 'in', ['private', 'public']]
                    $eventFilterValue = match ($this->event?->is_private) {
                        true => 'private',
                        false => 'public',
                        default => null,
                    };
                    break;

                // ... other filters ...

                default:
                    $knownFilter = false;
                    break;
            }

            if ($knownFilter && !in_array($eventFilterValue, $filterValues ?? [])) {
                return false;
            }
        }

        // all passed
        return true;
    }

    public function recipients(): array
    {
        return [
            ...parent::recipients(),
            'event.manager' => fn() => $this->event?->manager ?? $this->event?->user,
            'event.users' => fn() => $this->event?->activeEventUsers?->pluck('user') ?? collect(),
            'event.users.players' => fn() => $this->findEventUsersByStatuses(self::PLAYER_STATUSES),
            'event.users.applicants' => fn() => $this->findEventUsersByStatuses(self::APPLICANT_STATUSES),
        ];
    }

    public function recipientDescriptions(): array
    {
        return [
            ...parent::recipientDescriptions(),
            'event.users' => 'all players in this event',
            'event.users.players' => 'players in the roster of this event',
            'event.users.applicants' => 'players with an application to this event',
        ];
    }

    /**
     * @param EventUserStatusEnum[] $statuses
     */
    private function findEventUsersByStatuses(array $statuses): Collection
    {
        return $this->event?->activeEventUsers
            ?->filter(fn (EventUser $eventUser) => in_array($eventUser->status, $statuses, true))
            ->pluck('user') ?? collect();
    }

    public function variables(): array
    {
        return [
            ...parent::variables(),

            'event.name',

            'event.datetime' => fn() => EventHelper::getDateTime($this->event, plainText: true),
            'event.date' => fn() => EventHelper::getDate($this->event, plainText: true),
            'event.time' => fn() => EventHelper::getTime($this->event, plainText: true),
            'event.duration' => fn() => EventHelper::getDuration($this->event),
            'event.time_range' => fn() => EventHelper::getTimeRange($this->event?->time_start, $this->event?->time_end, plainText: true),

            'event.url' => fn() => $this->event?->b2cUrl,
            'event.url_b2b' => fn() => $this->event?->b2bUrl,

            'event.location' => fn() => EventHelper::getLocation($this->event, plainText: true),
            'event.levels' => fn() => EventHelper::getUserLevel($this->event?->min_user_level, $this->event?->max_user_level, plainText: true),
            'event.gender' => fn() => EventHelper::getUserGender($this->event?->gender, plainText: true),
            'event.cost' => fn() => EventHelper::getCostPerPlayer($this->event?->cost, plainText: true),

            'event.players_count' => fn() => EventHelper::getCurrentMaxPlayersCountInfo($this->event, plainText: true),
            'event.free_spots_count' => fn() => EventHelper::getFreeSpotsCount($this->event),
            'event.courts_count' => fn() => EventHelper::getCourtsCountInfo($this->event, plainText: true),

            'event.manager' => fn() => $this->event?->manager?->name ?? $this->event?->user?->name,

            'event.fee_terms' => fn() => NotificationHelper::getFeeTerms(),
        ];
    }

    public function extraData(): NotificationMessageExtraData|null
    {
        return new NotificationMessageExtraData(
            triggerType: $this->type(),
            eventId: $this->event?->id,
            eventSlug: $this->event?->slug,
            eventType: $this->event?->type,
        );
    }

    protected function uniqueIdData(): array
    {
        return [
            'type' => $this->type()->value,
            'event_id' => $this->event?->id,
        ];
    }
}
