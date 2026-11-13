<?php

namespace App\Modules\Notification\V1\Tests\Integration;

use App\Modules\Common\V1\Tests\TestCase;
use App\Modules\Event\V1\Enums\EventTypeEnum;
use App\Modules\Event\V1\Models\Event;
use App\Modules\Event\V1\Models\EventUser;
use App\Modules\Event\V1\Services\Event\Dto\DeleteEventDto;
use App\Modules\Event\V1\Services\Event\EventService;
use App\Modules\Notification\V1\Enums\NotificationDeliveryStatusEnum;
use App\Modules\Notification\V1\Enums\NotificationMessageStatusEnum;
use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\Models\Notification;
use App\Modules\Notification\V1\Models\NotificationCascade;
use App\Modules\Notification\V1\Models\NotificationDelivery;
use App\Modules\Notification\V1\Models\NotificationMessage;
use App\Modules\Notification\V1\Models\NotificationTemplate;
use App\Modules\Notification\V1\Notifications\Channels\DatabaseChannel;
use App\Modules\Notification\V1\Notifications\Channels\PushChannel;
use App\Modules\Notification\V1\Notifications\Channels\SmsChannel;
use App\Modules\Notification\V1\Notifications\Channels\TelegramChannel;
use App\Modules\Notification\V1\NotificationTriggers\Event\EventCancelledTrigger;
use App\Modules\Notification\V1\Services\Push\PushServiceInterface;
use App\Modules\Notification\V1\Services\Sms\SmsServiceInterface;
use App\Modules\Notification\V1\Services\Telegram\TelegramServiceInterface;
use App\Modules\Notification\V1\ValueObjects\NotificationChannelCascade;
use App\Modules\User\V1\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;

class ComplexNotificationE2ETest extends TestCase
{
    use DatabaseTransactions;

    public function testNotificationIsSentToDbChannelFailedToPushChannelAndSentToTelegramChannelAndNotTriedToSendToSmsChannel()
    {
        $user = User::factory()->create();

        $event = Event::factory()->create();
        $eventUser = EventUser::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);

        $triggerToFire = new EventCancelledTrigger(
            eventId: $event->id
        );

        $cascade = NotificationCascade::factory()->create([
            'channel_cascades' => [
                new NotificationChannelCascade(
                    channelName: DatabaseChannel::NAME
                ),
                new NotificationChannelCascade(
                    channelName: PushChannel::NAME,
                    onFail: new NotificationChannelCascade(
                        channelName: TelegramChannel::NAME,
                        onFail: new NotificationChannelCascade(
                            channelName: SmsChannel::NAME
                        )
                    )
                ),
            ],
        ]);
        $template = NotificationTemplate::factory()->create([
            'cascade_id' => $cascade->id,
            'active' => true,
            'trigger_type' => $triggerToFire->type(),
            'recipients' => [
                'event.manager',
            ],
            'content' => [
                DatabaseChannel::NAME => [
                    'title' => 'Test db title',
                    'text' => 'Test db text',
                ],
                PushChannel::NAME => [
                    'title' => 'Test push title',
                    'text' => 'Test push text',
                ],
                TelegramChannel::NAME => [
                    'message' => 'Test tg message',
                ],
                SmsChannel::NAME => [
                    'message' => 'Test sms message',
                ],
            ],
        ]);

        $this->mock(PushServiceInterface::class, function ($mock) use ($user) {
            $mock
                ->shouldReceive('send')
                ->once()
                ->andReturn(false); // fake FAILED behavior
        });
        $this->mock(TelegramServiceInterface::class, function ($mock) use ($user) {
            $mock
                ->shouldReceive('send')
                ->once()
                ->andReturn(true);
        });
        $this->mock(SmsServiceInterface::class, function ($mock) {
            $mock
                ->shouldReceive('send')
                ->never(); // this should NEVER be called as notification to Telegram has been sent
        });

        Queue::fake();


        // act
        NotificationTrigger::fire($triggerToFire);
        $this->handleDispatchedJobs();


        // assert db-notification is created AND delivery is marked as sent
        $this->assertDatabaseCount(Notification::class, 1);
        $this->assertDatabaseHas(Notification::class, [
            'type' => $triggerToFire->type(),
            'title' => 'Test db title',
            'text' => 'Test db text',
        ]);

        $delivery = NotificationDelivery::query()
            ->where('message_id', NotificationMessage::first()->id)
            ->where('channel_name', DatabaseChannel::NAME)
            ->first();

        $this->assertEquals(NotificationDeliveryStatusEnum::SENT, $delivery->status);

        // check notification extra data as well
        $this->assertEqualsCanonicalizing(
            [
                'event_id' => $event->id,
                'event_slug' => $event->slug,
                'event_type' => $event->type->value,
            ],
            Notification::first()->data
        );

        // assert push and tg deliveries are in final statuses and NO sms delivery is called
        $pushDelivery = NotificationDelivery::query()
            ->where('message_id', NotificationMessage::first()->id)
            ->where('channel_name', PushChannel::NAME)
            ->first();
        $this->assertEquals(NotificationDeliveryStatusEnum::FAILED, $pushDelivery->status);

        $tgDelivery = NotificationDelivery::query()
            ->where('message_id', NotificationMessage::first()->id)
            ->where('channel_name', TelegramChannel::NAME)
            ->first();
        $this->assertEquals(NotificationDeliveryStatusEnum::SENT, $tgDelivery->status);

        $smsDelivery = NotificationDelivery::query()
            ->where('message_id', NotificationMessage::first()->id)
            ->where('channel_name', SmsChannel::NAME)
            ->first();
        $this->assertNull($smsDelivery); // NO sms trial at all

        // assert message status is "successful"
        $this->assertDatabaseCount(NotificationMessage::class, 1); // to be sure we see right message
        $message = NotificationMessage::first();
        $this->assertEquals(NotificationMessageStatusEnum::SUCCESSFUL, $message->status);
    }

    public function testNotificationIsSentToDbChannelFailedToPushChannelSkippedTelegramChannelAndSentToSmsChannel()
    {
        $user = User::factory()->create();

        $event = Event::factory()->create();
        $eventUser = EventUser::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);

        $triggerToFire = new EventCancelledTrigger(
            eventId: $event->id
        );

        $cascade = NotificationCascade::factory()->create([
            'channel_cascades' => [
                new NotificationChannelCascade(
                    channelName: DatabaseChannel::NAME
                ),
                new NotificationChannelCascade(
                    channelName: PushChannel::NAME,
                    onFail: new NotificationChannelCascade(
                        channelName: TelegramChannel::NAME,
                        onFail: new NotificationChannelCascade(
                            channelName: SmsChannel::NAME
                        )
                    )
                ),
            ],
        ]);
        $template = NotificationTemplate::factory()->create([
            'cascade_id' => $cascade->id,
            'active' => true,
            'trigger_type' => $triggerToFire->type(),
            'recipients' => [
                'event.manager',
            ],
            'content' => [
                DatabaseChannel::NAME => [
                    'title' => 'Test db title',
                    'text' => 'Test db text',
                ],
                PushChannel::NAME => [
                    'title' => 'Test push title',
                    'text' => 'Test push text',
                ],
                TelegramChannel::NAME => [
                    'message' => '', // no content - should be skipped
                ],
                SmsChannel::NAME => [
                    'message' => 'Test sms message',
                ],
            ],
        ]);

        $this->mock(PushServiceInterface::class, function ($mock) use ($user) {
            $mock
                ->shouldReceive('send')
                ->once()
                ->andReturn(false); // fake FAILED behavior
        });
        $this->mock(TelegramServiceInterface::class, function ($mock) use ($user) {
            $mock
                ->shouldReceive('send')
                ->never(); // expected to be skipped before trying to send
        });
        $this->mock(SmsServiceInterface::class, function ($mock) {
            $mock
                ->shouldReceive('send')
                ->once()
                ->andReturn(true); // fake SENT behavior
        });

        Queue::fake();


        // act
        NotificationTrigger::fire($triggerToFire);
        $this->handleDispatchedJobs();


        // assert db-notification is created AND delivery is marked as sent
        $this->assertDatabaseCount(Notification::class, 1);
        $this->assertDatabaseHas(Notification::class, [
            'type' => $triggerToFire->type(),
            'title' => 'Test db title',
            'text' => 'Test db text',
        ]);

        $delivery = NotificationDelivery::query()
            ->where('message_id', NotificationMessage::first()->id)
            ->where('channel_name', DatabaseChannel::NAME)
            ->first();

        $this->assertEquals(NotificationDeliveryStatusEnum::SENT, $delivery->status);

        // check notification extra data as well
        $this->assertEqualsCanonicalizing(
            [
                'event_id' => $event->id,
                'event_slug' => $event->slug,
                'event_type' => $event->type->value,
            ],
            Notification::first()->data,
        );

        // assert push and tg deliveries are in final statuses and NO sms delivery is called
        $pushDelivery = NotificationDelivery::query()
            ->where('message_id', NotificationMessage::first()->id)
            ->where('channel_name', PushChannel::NAME)
            ->first();
        $this->assertEquals(NotificationDeliveryStatusEnum::FAILED, $pushDelivery->status);

        $tgDelivery = NotificationDelivery::query()
            ->where('message_id', NotificationMessage::first()->id)
            ->where('channel_name', TelegramChannel::NAME)
            ->first();
        $this->assertEquals(NotificationDeliveryStatusEnum::SKIPPED, $tgDelivery->status); // created but skipped

        $smsDelivery = NotificationDelivery::query()
            ->where('message_id', NotificationMessage::first()->id)
            ->where('channel_name', SmsChannel::NAME)
            ->first();
        $this->assertEquals(NotificationDeliveryStatusEnum::SENT, $smsDelivery->status);

        // assert message status is "successful"
        $this->assertDatabaseCount(NotificationMessage::class, 1); // to be sure we see right message
        $message = NotificationMessage::first();
        $this->assertEquals(NotificationMessageStatusEnum::SUCCESSFUL, $message->status);
    }

    public function testPushNotificationExpiresAfterTimeoutAndGoesToTelegramChannel()
    {
        $user = User::factory()->create();

        $event = Event::factory()->create();
        $eventUser = EventUser::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);

        $triggerToFire = new EventCancelledTrigger(
            eventId: $event->id
        );

        $timeout = 600; // 10 min
        $cascade = NotificationCascade::factory()->create([
            'channel_cascades' => [
                new NotificationChannelCascade(
                    channelName: PushChannel::NAME,
                    timeout: $timeout,
                    onFail: new NotificationChannelCascade(
                        channelName: TelegramChannel::NAME,
                    )
                ),
            ],
        ]);
        $template = NotificationTemplate::factory()->create([
            'cascade_id' => $cascade->id,
            'active' => true,
            'trigger_type' => $triggerToFire->type(),
            'recipients' => [
                'event.manager',
            ],
            'content' => [
                DatabaseChannel::NAME => [
                    'title' => 'Test db title',
                    'text' => 'Test db text',
                ],
                PushChannel::NAME => [
                    'title' => 'Test push title',
                    'text' => 'Test push text',
                ],
                TelegramChannel::NAME => [
                    'message' => 'Test tg message',
                ],
                SmsChannel::NAME => [
                    'message' => 'Test sms message',
                ],
            ],
        ]);

        $this->mock(PushServiceInterface::class, function ($mock) use ($user) {
            $mock
                ->shouldReceive('send')
                ->once()
                ->andReturn(null); // it does NOT  report success or failure (delivery should stall in sending state)
        });
        $this->mock(TelegramServiceInterface::class, function ($mock) use ($user) {
            $mock
                ->shouldReceive('send') // will be called after Push timeout is expired
                ->once()
                ->andReturn(true); // fake SENT behavior
        });

        Queue::fake();


        // act #1 - fire trigger to create message
        NotificationTrigger::fire($triggerToFire);
        $this->handleDispatchedJobs();


        // assert delivery and message are in "sending" state
        $this->assertDatabaseCount(NotificationDelivery::class, 1);
        $this->assertDatabaseHas(NotificationDelivery::class, [
            'message_id' => NotificationMessage::first()->id,
            'channel_name' => PushChannel::NAME,
            'status' => NotificationDeliveryStatusEnum::SENDING,
        ]);

        $this->assertDatabaseCount(NotificationMessage::class, 1);
        $this->assertDatabaseHas(NotificationMessage::class, [
            'id' => NotificationMessage::first()->id,
            'status' => NotificationMessageStatusEnum::SENDING,
        ]);


        // act #2 - simulate artisan call from cron after HALF of timeout - nothing should happen
        Carbon::setTestNow(now()->addSeconds($timeout / 2));
        $this->artisan('app:process-active-notification-messages');
        $this->artisan('app:process-active-notification-deliveries');
        $this->handleDispatchedJobs();


        // assert delivery and message are still in "sending" state
        $this->assertDatabaseHas(NotificationDelivery::class, [
            'message_id' => NotificationMessage::first()->id,
            'channel_name' => PushChannel::NAME,
            'status' => NotificationDeliveryStatusEnum::SENDING,
        ]);
        $this->assertDatabaseHas(NotificationMessage::class, [
            'id' => NotificationMessage::first()->id,
            'status' => NotificationMessageStatusEnum::SENDING,
        ]);


        // act #3 - simulate artisan call from cron after FULL timeout - delivery should expire and go to Telegram channel
        Carbon::setTestNow(now()->addSeconds($timeout + 1));
        $this->artisan('app:process-active-notification-messages');
        $this->artisan('app:process-active-notification-deliveries');
        $this->handleDispatchedJobs();


        // assert delivery is expired, telegram delivery is sent and message is "successful" now
        $this->assertDatabaseHas(NotificationDelivery::class, [
            'message_id' => NotificationMessage::first()->id,
            'channel_name' => PushChannel::NAME,
            'status' => NotificationDeliveryStatusEnum::FAILED,
        ]);
        $this->assertDatabaseHas(NotificationDelivery::class, [
            'message_id' => NotificationMessage::first()->id,
            'channel_name' => TelegramChannel::NAME,
            'status' => NotificationDeliveryStatusEnum::SENT,
        ]);
        $this->assertDatabaseHas(NotificationMessage::class, [
            'id' => NotificationMessage::first()->id,
            'status' => NotificationMessageStatusEnum::SUCCESSFUL,
        ]);
    }

    public function testTotalDeliveryFailure()
    {
        $user = User::factory()->create();

        $event = Event::factory()->create();
        $eventUser = EventUser::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);

        $triggerToFire = new EventCancelledTrigger(
            eventId: $event->id
        );

        // full cascade
        $cascade = NotificationCascade::factory()->create([
            'channel_cascades' => [
                new NotificationChannelCascade(
                    channelName: DatabaseChannel::NAME
                ),
                new NotificationChannelCascade(
                    channelName: PushChannel::NAME,
                    onFail: new NotificationChannelCascade(
                        channelName: TelegramChannel::NAME,
                        onFail: new NotificationChannelCascade(
                            channelName: SmsChannel::NAME
                        )
                    )
                ),
            ],
        ]);
        $template = NotificationTemplate::factory()->create([
            'cascade_id' => $cascade->id,
            'active' => true,
            'trigger_type' => $triggerToFire->type(),
            'recipients' => [
                'event.manager',
            ],
            'content' => [
                DatabaseChannel::NAME => [
                    'title' => 'Test db title',
                    'text' => 'Test db text',
                ],
                PushChannel::NAME => [
                    'title' => 'Test push title',
                    'text' => 'Test push text',
                ],
                TelegramChannel::NAME => [
                    'message' => 'Test tg message',
                ],
                SmsChannel::NAME => [
                    'message' => 'Test sms message',
                ],
            ],
        ]);

        $this->mock(PushServiceInterface::class, function ($mock) use ($user) {
            $mock
                ->shouldReceive('send')
                ->once()
                ->andReturn(false); // fake FAILED behavior
        });
        $this->mock(TelegramServiceInterface::class, function ($mock) use ($user) {
            $mock
                ->shouldReceive('send')
                ->once()
                ->andReturn(false); // fake FAILED behavior
        });
        $this->mock(SmsServiceInterface::class, function ($mock) use ($user) {
            $mock
                ->shouldReceive('send')
                ->once()
                ->andReturn(false); // fake FAILED behavior
        });

        Queue::fake();


        // act
        NotificationTrigger::fire($triggerToFire);
        $this->handleDispatchedJobs();


        // assert all deliveries (apart from db) are in "failed" state
        $this->assertDatabaseCount(NotificationDelivery::class, 4);
        $this->assertDatabaseHas(NotificationDelivery::class, [
            'message_id' => NotificationMessage::first()->id,
            'channel_name' => DatabaseChannel::NAME,
            'status' => NotificationDeliveryStatusEnum::SENT,
        ]);
        $this->assertDatabaseHas(NotificationDelivery::class, [
            'message_id' => NotificationMessage::first()->id,
            'channel_name' => PushChannel::NAME,
            'status' => NotificationDeliveryStatusEnum::FAILED,
        ]);
        $this->assertDatabaseHas(NotificationDelivery::class, [
            'message_id' => NotificationMessage::first()->id,
            'channel_name' => TelegramChannel::NAME,
            'status' => NotificationDeliveryStatusEnum::FAILED,
        ]);
        $this->assertDatabaseHas(NotificationDelivery::class, [
            'message_id' => NotificationMessage::first()->id,
            'channel_name' => SmsChannel::NAME,
            'status' => NotificationDeliveryStatusEnum::FAILED,
        ]);

        // assert message status is "failed"
        $this->assertDatabaseCount(NotificationMessage::class, 1); // to be sure we see right message
        $message = NotificationMessage::first();
        $this->assertEquals(NotificationMessageStatusEnum::FAILED, $message->status);
    }

    // "trashed event" bug case test
    // (trigger subsystem must work with events/tournaments being already trashed, otherwise "cancel event" notification is never fired)
    public function testDeleteMatchStillFiresEventCancelledTrigger(): void
    {
        $user = User::factory()->create();

        $event = Event::factory()->create([
            'type' => EventTypeEnum::MATCH->value,
            'user_id' => $user->id,
            'manager_id' => $user->id,
        ]);

        $cascade = NotificationCascade::factory()->create([
            'channel_cascades' => [
                new NotificationChannelCascade(
                    channelName: DatabaseChannel::NAME
                ),
            ],
        ]);
        $template = NotificationTemplate::factory()->create([
            'cascade_id' => $cascade->id,
            'active' => true,
            'trigger_type' => EventCancelledTrigger::empty()->type(),
            'recipients' => [
                'event.manager',
            ],
            'content' => [
                DatabaseChannel::NAME => [
                    'title' => 'Test db title',
                    'text' => 'Test db text',
                ],
            ],
        ]);

        Queue::fake();

        app(EventService::class)->delete(new DeleteEventDto(
            id: $event->id,
            sendToCrm: false,
        ));
        $this->handleDispatchedJobs();

        $this->assertDatabaseCount(Notification::class, 1);
        $this->assertDatabaseHas(Notification::class, [
            'type' => EventCancelledTrigger::empty()->type(),
        ]);
    }
}
