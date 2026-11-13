<?php

namespace App\Modules\Notification\V1\Tests\Integration\Triggers;

use App\Modules\Common\V1\Tests\TestCase;
use App\Modules\Event\V1\Enums\UpcomingEventNotificationStageEnum;
use App\Modules\Event\V1\Models\Event;
use App\Modules\Event\V1\Models\EventUser;
use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\Models\NotificationMessage;
use App\Modules\Notification\V1\Models\NotificationTemplate;
use App\Modules\Notification\V1\NotificationTriggers\Event\EventUpcomingTrigger;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;

class EventUpcomingTriggerTest extends TestCase
{
    use DatabaseTransactions;

    public function testSentOnlyTemplatesMatchingStageFilters()
    {
        $event = Event::factory()->create();
        $eventUsers = EventUser::factory()->count(2)->create([
            'event_id' => $event->id,
        ]);
        $players = $eventUsers->map(fn(EventUser $eventUser) => $eventUser->user);

        $templateForEarlierNotification = NotificationTemplate::factory()->create([
            'trigger_type' => EventUpcomingTrigger::empty()->type(),
            'filters' => [
                ['notification_stage', 'in', [UpcomingEventNotificationStageEnum::EARLIER->value]],
            ],
            'recipients' => [
                'event.users',
            ],
        ]);
        $templateForNotification1 = NotificationTemplate::factory()->create([
            'trigger_type' => EventUpcomingTrigger::empty()->type(),
            'filters' => [
                ['notification_stage', 'in', [UpcomingEventNotificationStageEnum::NOTIFICATION_1->value]],
            ],
            'recipients' => [
                'event.users',
            ],
        ]);
        $templateForNotification2 = NotificationTemplate::factory()->create([
            'trigger_type' => EventUpcomingTrigger::empty()->type(),
            'filters' => [
                ['notification_stage', 'in', [UpcomingEventNotificationStageEnum::NOTIFICATION_2->value]],
            ],
            'recipients' => [
                'event.users',
            ],
        ]);
        $templateForNotification3 = NotificationTemplate::factory()->create([
            'trigger_type' => EventUpcomingTrigger::empty()->type(),
            'filters' => [
                ['notification_stage', 'in', [UpcomingEventNotificationStageEnum::NOTIFICATION_3->value]],
            ],
            'recipients' => [
                'event.users',
            ],
        ]);

        Queue::fake();


        NotificationTrigger::fire(new EventUpcomingTrigger(
            eventId: $event->id,
            notificationStage: UpcomingEventNotificationStageEnum::NOTIFICATION_1, // only $templateForNotification1 texts should be sent
        ));
        $this->handleDispatchedJobs();


        $this->assertDatabaseCount(NotificationMessage::class, $players->count());
        foreach ($players as $player) {
            $this->assertDatabaseHas(NotificationMessage::class, [
                'recipient_id' => $player->id,
                'template_id' => $templateForNotification1->id,
            ]);
        }
    }
}
