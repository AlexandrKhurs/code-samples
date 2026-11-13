<?php

namespace App\Modules\Notification\V1\Tests\Integration\Listeners;

use App\Modules\Common\V1\Tests\TestCase;
use App\Modules\Event\V1\Events\EventCreatedEvent;
use App\Modules\Event\V1\Models\Event;
use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\NotificationTriggers\Event\EventCreatedTrigger;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class EventCreatedListenerTest extends TestCase
{
    use DatabaseTransactions;

    public function testEventCreatedTriggerFiredWhenNotBooking()
    {
        NotificationTrigger::fake();

        $event = Event::factory()->create(['type' => \App\Modules\Event\V1\Enums\EventTypeEnum::MATCH->value]);

        event(new EventCreatedEvent(event: $event));

        NotificationTrigger::assertFired(EventCreatedTrigger::class);
    }

    public function testFiresTriggerWhenEventIsBooking()
    {
        NotificationTrigger::fake();

        $event = Event::factory()->create(['type' => \App\Modules\Event\V1\Enums\EventTypeEnum::BOOKING->value]);

        event(new EventCreatedEvent(event: $event));

        NotificationTrigger::assertFired(EventCreatedTrigger::class);
    }
}
