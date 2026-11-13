<?php

namespace App\Modules\Notification\V1\Tests\Integration;

use App\Modules\Common\V1\Tests\TestCase;
use App\Modules\Event\V1\Models\Event;
use App\Modules\Notification\V1\Enums\NotificationDeliveryStatusEnum;
use App\Modules\Notification\V1\Events\NotificationDeliveryStatusUpdatedEvent;
use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\Models\Notification;
use App\Modules\Notification\V1\Models\NotificationCascade;
use App\Modules\Notification\V1\Models\NotificationDelivery;
use App\Modules\Notification\V1\Models\NotificationMessage;
use App\Modules\Notification\V1\Models\NotificationTemplate;
use App\Modules\Notification\V1\Notifications\Channels\DatabaseChannel;
use App\Modules\Notification\V1\Notifications\Channels\TelegramChannel;
use App\Modules\Notification\V1\Services\NotificationDelivery\Dto\CreateNotificationDeliveryDto;
use App\Modules\Notification\V1\Services\NotificationDelivery\NotificationDeliveryProcessor;
use App\Modules\Notification\V1\Services\NotificationDelivery\NotificationDeliveryService;
use App\Modules\Notification\V1\Services\NotificationTrigger\NotificationTriggerService;
use App\Modules\Notification\V1\ValueObjects\NotificationChannelCascade;
use App\Modules\Notification\V1\ValueObjects\NotificationGroupSettings;
use App\Modules\Notification\V1\ValueObjects\NotificationMessageExtraData;
use App\Modules\User\V1\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event as LaravelEvent;
use PHPUnit\Framework\Attributes\DataProvider;

class NotificationDeliveryProcessorTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2001-01-01 12:00:00');
    }

    public function testCalledOnNotificationDeliveryCreated()
    {
        $message = NotificationMessage::factory()->create();

        $this->mock(NotificationDeliveryProcessor::class)
            ->shouldReceive('process')
            ->once();

        app(NotificationDeliveryService::class)->create(new CreateNotificationDeliveryDto(
            messageId: $message->id,
            channelName: DatabaseChannel::NAME,
            content: $message->content[DatabaseChannel::NAME] ?? [],
        ));
    }

    #[DataProvider('deliverySendableStatusesProvider')]
    public function testSendsDeliveryIfCreatedOrPostponedAndSendAfterIsNowOrInPastAndFiresStatusUpdatedEvent(
        NotificationDeliveryStatusEnum $status
    ) {
        $event = Event::factory()->create();
        $cascade = NotificationCascade::factory()->create();
        $template = NotificationTemplate::factory()->create([
            'cascade_id' => $cascade->id,
        ]);
        $messageContent = [
            DatabaseChannel::NAME => [
                'title' => 'Test title',
                'text' => 'Test text',
            ],
        ];
        $messageExtraData = new NotificationMessageExtraData(
            triggerType: $template->trigger_type,
            eventId: $event->id,
            eventType: $event->type,
            uuid: fake()->uuid,
        );
        $message = NotificationMessage::factory()->create([
            'trigger_type' => $template->trigger_type,
            'uuid' => $messageExtraData->uuid,
            'template_id' => $template->id,
            'cascade_id' => $cascade->id,
            'content' => $messageContent,
            'extra_data' => $messageExtraData,
        ]);
        $delivery = NotificationDelivery::factory()->create([
            'message_id' => $message->id,
            'channel_name' => DatabaseChannel::NAME,
            'status' => $status,
            'content' => $messageContent[DatabaseChannel::NAME],
            'send_after' => now(),
            'extra_data' => $messageExtraData,
        ]);

        LaravelEvent::fake([
            NotificationDeliveryStatusUpdatedEvent::class, // avoid futher message processing after delivery is processed
        ]);

        app(NotificationDeliveryProcessor::class)->process($delivery->id);

        $this->assertDatabaseCount(NotificationDelivery::class, 1);
        $this->assertDatabaseHas(NotificationDelivery::class, [
            'id' => $delivery->id,
            'status' => NotificationDeliveryStatusEnum::SENT, // processor sets to 'sending' status, and then channel sets it to 'sent' after successfull sending
            'sending_started_at' => now(),
        ]);

        $this->assertDatabaseHas(Notification::class, [ // assert DB-notification really created
            'uuid' => $message->uuid,
            'type' => $template->trigger_type,
            'title' => $delivery->content['title'],
            'text' => $delivery->content['text'],
        ]);

        LaravelEvent::assertDispatched(NotificationDeliveryStatusUpdatedEvent::class);
    }

    #[DataProvider('deliverySendableStatusesProvider')]
    public function testPostponesDeliveryIfCreatedOrPostponedAndSendAfterIsInFuture(
        NotificationDeliveryStatusEnum $status
    ) {
        $cascade = NotificationCascade::factory()->create();
        $template = NotificationTemplate::factory()->create([
            'cascade_id' => $cascade->id,
        ]);
        $message = NotificationMessage::factory()->create([
            'template_id' => $template->id,
            'cascade_id' => $cascade->id,
        ]);
        $delivery = NotificationDelivery::factory()->create([
            'message_id' => $message->id,
            'channel_name' => DatabaseChannel::NAME,
            'status' => $status,
            'send_after' => now()->addMinute(),
        ]);

        LaravelEvent::fake([
            NotificationDeliveryStatusUpdatedEvent::class, // avoid futher message processing after delivery is processed
        ]);

        app(NotificationDeliveryProcessor::class)->process($delivery->id);

        $this->assertDatabaseCount(NotificationDelivery::class, 1);
        $this->assertDatabaseHas(NotificationDelivery::class, [
            'id' => $delivery->id,
            'status' => NotificationDeliveryStatusEnum::POSTPONED,
        ]);

        $this->assertDatabaseCount(Notification::class, 0);
    }

    public static function deliverySendableStatusesProvider(): array
    {
        return [
            'created' => [NotificationDeliveryStatusEnum::CREATED],
            'postponed' => [NotificationDeliveryStatusEnum::POSTPONED],
        ];
    }

    public function testIgnoresDeliveryIfSendingAndTimeoutIsNotSet()
    {
        $cascade = NotificationCascade::factory()->create();
        $template = NotificationTemplate::factory()->create([
            'cascade_id' => $cascade->id,
        ]);
        $message = NotificationMessage::factory()->create([
            'template_id' => $template->id,
            'cascade_id' => $cascade->id,
        ]);
        $delivery = NotificationDelivery::factory()->create([
            'message_id' => $message->id,
            'channel_name' => DatabaseChannel::NAME,
            'status' => NotificationDeliveryStatusEnum::SENDING,
            'created_at' => now(),
        ]);

        LaravelEvent::fake([
            NotificationDeliveryStatusUpdatedEvent::class, // avoid futher message processing after delivery is processed
        ]);

        Carbon::setTestNow(now()->addMinutes(5)); // few moments later
        app(NotificationDeliveryProcessor::class)->process($delivery->id);

        $this->assertDatabaseCount(NotificationDelivery::class, 1);
        $this->assertDatabaseHas(NotificationDelivery::class, [
            'id' => $delivery->id,
            'status' => NotificationDeliveryStatusEnum::SENDING,
            'created_at' => $delivery->created_at,
            'updated_at' => $delivery->created_at, // NO update when processing
        ]);
    }

    public function testIgnoresDeliveryIfSendingAndTimeoutDoesNotExpire()
    {
        $timeout = 10;
        $timePassed = 5;
        $cascade = NotificationCascade::factory()->create(['channel_cascades' => [
            new NotificationChannelCascade(channelName: DatabaseChannel::NAME, timeout: $timeout),
        ]]);
        $template = NotificationTemplate::factory()->create([
            'cascade_id' => $cascade->id,
        ]);
        $message = NotificationMessage::factory()->create([
            'template_id' => $template->id,
            'cascade_id' => $cascade->id,
        ]);
        $delivery = NotificationDelivery::factory()->create([
            'message_id' => $message->id,
            'channel_name' => DatabaseChannel::NAME,
            'status' => NotificationDeliveryStatusEnum::SENDING,
            'sending_started_at' => now(),
            'timeout' => $timeout,
            'created_at' => now(),
        ]);

        LaravelEvent::fake([
            NotificationDeliveryStatusUpdatedEvent::class, // avoid futher message processing after delivery is processed
        ]);

        Carbon::setTestNow(now()->addSeconds($timePassed)); // few moments later
        app(NotificationDeliveryProcessor::class)->process($delivery->id);

        $this->assertDatabaseCount(NotificationDelivery::class, 1);
        $this->assertDatabaseHas(NotificationDelivery::class, [
            'id' => $delivery->id,
            'status' => NotificationDeliveryStatusEnum::SENDING,
            'created_at' => $delivery->created_at,
            'updated_at' => $delivery->created_at, // NO update when processing
        ]);
    }

    public function testSkipsDeliveryIfEmptyContent()
    {
        $cascade = NotificationCascade::factory()->create();
        $template = NotificationTemplate::factory()->create([
            'cascade_id' => $cascade->id,
        ]);
        $message = NotificationMessage::factory()->create([
            'template_id' => $template->id,
            'cascade_id' => $cascade->id,
        ]);
        $delivery = NotificationDelivery::factory()->create([
            'message_id' => $message->id,
            'channel_name' => DatabaseChannel::NAME,
            'status' => NotificationDeliveryStatusEnum::CREATED,
            'created_at' => now(),
            'content' => [], // empty!
        ]);

        LaravelEvent::fake([
            NotificationDeliveryStatusUpdatedEvent::class, // avoid futher message processing after delivery is processed
        ]);

        app(NotificationDeliveryProcessor::class)->process($delivery->id);

        $this->assertDatabaseCount(NotificationDelivery::class, 1);
        $this->assertDatabaseHas(NotificationDelivery::class, [
            'id' => $delivery->id,
            'status' => NotificationDeliveryStatusEnum::SKIPPED,
        ]);
    }

    public function testSkipsDeliveryIfEmptyContentFields()
    {
        $cascade = NotificationCascade::factory()->create();
        $template = NotificationTemplate::factory()->create([
            'cascade_id' => $cascade->id,
        ]);
        $message = NotificationMessage::factory()->create([
            'template_id' => $template->id,
            'cascade_id' => $cascade->id,
        ]);
        $delivery = NotificationDelivery::factory()->create([
            'message_id' => $message->id,
            'channel_name' => DatabaseChannel::NAME,
            'status' => NotificationDeliveryStatusEnum::CREATED,
            'created_at' => now(),
            'content' => [
                'title' => '',
                'text' => '',
            ],
        ]);

        LaravelEvent::fake([
            NotificationDeliveryStatusUpdatedEvent::class, // avoid futher message processing after delivery is processed
        ]);

        app(NotificationDeliveryProcessor::class)->process($delivery->id);

        $this->assertDatabaseCount(NotificationDelivery::class, 1);
        $this->assertDatabaseHas(NotificationDelivery::class, [
            'id' => $delivery->id,
            'status' => NotificationDeliveryStatusEnum::SKIPPED,
        ]);
    }

    public function testSkipsDeliveryIfDisabledByUser()
    {
        $triggerType = NotificationTrigger::getTriggerNotificationTypes()->first();
        $trigger = app(NotificationTriggerService::class)->getTrigger($triggerType);
        $triggerGroup = $trigger?->group();

        // user with disabled Telegram channel for notification group $triggerGroup (group our template and message belongs to)
        $user = User::factory()->create();
        $user->update([
            'notification_settings' => NotificationGroupSettings::default()->merge([
                $triggerGroup->value => [
                    TelegramChannel::NAME => false,
                ],
            ]),
        ]);

        $cascade = NotificationCascade::factory()->create();
        $template = NotificationTemplate::factory()->create([
            'trigger_type' => $triggerType,
            'cascade_id' => $cascade->id,
        ]);
        $message = NotificationMessage::factory()->create([
            'trigger_type' => $triggerType,
            'template_id' => $template->id,
            'cascade_id' => $cascade->id,
            'recipient_id' => $user->id,
            'content' => [
                TelegramChannel::NAME => [
                    'message' => 'test Telegram channel message',
                ],
            ],
        ]);
        $delivery = NotificationDelivery::factory()->create([
            'message_id' => $message->id,
            'channel_name' => TelegramChannel::NAME, // cand use DatabaseChannel here as it cant be disabled by user
            'status' => NotificationDeliveryStatusEnum::CREATED,
            'created_at' => now(),
            'content' => [
                'message' => 'test Telegram channel message',
            ],
        ]);

        LaravelEvent::fake([
            NotificationDeliveryStatusUpdatedEvent::class, // avoid futher message processing after delivery is processed
        ]);

        app(NotificationDeliveryProcessor::class)->process($delivery->id);

        $this->assertDatabaseCount(NotificationDelivery::class, 1);
        $this->assertDatabaseHas(NotificationDelivery::class, [
            'id' => $delivery->id,
            'status' => NotificationDeliveryStatusEnum::SKIPPED,
        ]);
    }

    public function testExpiresDeliveryIfSendingAndTimeoutExpired()
    {
        $timeout = 10;
        $timePassed = 15;
        $cascade = NotificationCascade::factory()->create(['channel_cascades' => [
            new NotificationChannelCascade(channelName: DatabaseChannel::NAME, timeout: $timeout),
        ]]);
        $template = NotificationTemplate::factory()->create([
            'cascade_id' => $cascade->id,
        ]);
        $message = NotificationMessage::factory()->create([
            'template_id' => $template->id,
            'cascade_id' => $cascade->id,
        ]);
        $delivery = NotificationDelivery::factory()->create([
            'message_id' => $message->id,
            'channel_name' => DatabaseChannel::NAME,
            'status' => NotificationDeliveryStatusEnum::SENDING,
            'sending_started_at' => now(),
            'timeout' => $timeout,
            'created_at' => now(),
        ]);

        LaravelEvent::fake([
            NotificationDeliveryStatusUpdatedEvent::class,
        ]);

        Carbon::setTestNow(now()->addSeconds($timePassed)); // few moments later
        app(NotificationDeliveryProcessor::class)->process($delivery->id);

        $this->assertDatabaseCount(NotificationDelivery::class, 1);
        $this->assertDatabaseHas(NotificationDelivery::class, [
            'id' => $delivery->id,
            'status' => NotificationDeliveryStatusEnum::FAILED,
            'created_at' => $delivery->created_at,
            'updated_at' => now(), // just updated
        ]);
    }

    #[DataProvider('finalDeliveryStatusesProvider')]
    public function testIgnoresDeliveryIfInFinalStatus(
        NotificationDeliveryStatusEnum $status
    ) {
        $cascade = NotificationCascade::factory()->create();
        $template = NotificationTemplate::factory()->create([
            'cascade_id' => $cascade->id,
        ]);
        $message = NotificationMessage::factory()->create([
            'template_id' => $template->id,
            'cascade_id' => $cascade->id,
        ]);
        $delivery = NotificationDelivery::factory()->create([
            'message_id' => $message->id,
            'channel_name' => DatabaseChannel::NAME,
            'status' => $status,
        ]);

        LaravelEvent::fake([
            NotificationDeliveryStatusUpdatedEvent::class, // avoid futher message processing after delivery is processed
        ]);

        app(NotificationDeliveryProcessor::class)->process($delivery->id);

        $this->assertDatabaseCount(NotificationDelivery::class, 1);
        $this->assertDatabaseHas(NotificationDelivery::class, [
            'id' => $delivery->id,
            'status' => $status,
            'created_at' => $delivery->created_at,
            'updated_at' => $delivery->created_at, // NO update when processing
        ]);
    }

    public static function finalDeliveryStatusesProvider(): array
    {
        return collect(NotificationDeliveryStatusEnum::finalStatuses())
            ->mapWithKeys(fn($status) => [strtolower($status->name) => [$status]])
            ->toArray();
    }

    public function testProcessActiveNotificationDeliveriesProcessesAllActiveDeliveriesCommand()
    {
        $deliveryCreated = NotificationDelivery::factory()->create([
            'status' => NotificationDeliveryStatusEnum::CREATED,
        ]);
        $deliveryPostponed = NotificationDelivery::factory()->create([
            'status' => NotificationDeliveryStatusEnum::POSTPONED,
        ]);
        $deliverySending = NotificationDelivery::factory()->create([
            'status' => NotificationDeliveryStatusEnum::SENDING,
        ]);
        $deliveryFailed = NotificationDelivery::factory()->create([
            'status' => NotificationDeliveryStatusEnum::FAILED,
        ]);
        $deliverySkipped = NotificationDelivery::factory()->create([
            'status' => NotificationDeliveryStatusEnum::SKIPPED,
        ]);
        $deliverySent = NotificationDelivery::factory()->create([
            'status' => NotificationDeliveryStatusEnum::SENT,
        ]);
        $deliveryDelivered = NotificationDelivery::factory()->create([
            'status' => NotificationDeliveryStatusEnum::DELIVERED,
        ]);
        $deliveryRead = NotificationDelivery::factory()->create([
            'status' => NotificationDeliveryStatusEnum::READ,
        ]);

        $this->mock(
            NotificationDeliveryProcessor::class,
            function ($mock) use ($deliveryCreated, $deliveryPostponed, $deliverySending, $deliveryFailed, $deliverySkipped, $deliverySent, $deliveryDelivered, $deliveryRead) {
                return $mock
                    // active statusts
                    ->shouldReceive('process')->with($deliveryCreated->id)->once()
                    ->shouldReceive('process')->with($deliveryPostponed->id)->once()
                    ->shouldReceive('process')->with($deliverySending->id)->once()

                    // inactive statusts
                    ->shouldReceive('process')->with($deliveryFailed->id)->never()
                    ->shouldReceive('process')->with($deliverySkipped->id)->never()
                    ->shouldReceive('process')->with($deliverySent->id)->never()
                    ->shouldReceive('process')->with($deliveryDelivered->id)->never()
                    ->shouldReceive('process')->with($deliveryRead->id)->never();
            }
        );

        $this->artisan('app:process-active-notification-deliveries');
    }
}
