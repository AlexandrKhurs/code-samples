<?php

namespace App\Modules\Notification\V1\Tests\Factories;

use App\Modules\Notification\V1\Enums\NotificationDeliveryStatusEnum;
use App\Modules\Notification\V1\Models\NotificationDelivery;
use App\Modules\Notification\V1\Models\NotificationMessage;
use App\Modules\Notification\V1\Notifications\Channels\DatabaseChannel;
use App\Modules\Notification\V1\ValueObjects\NotificationMessageExtraData;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationDeliveryFactory extends Factory
{
    protected $model = NotificationDelivery::class;

    public function definition(): array
    {
        return [
            'message_id' => NotificationMessage::factory(),
            'channel_name' => DatabaseChannel::NAME,
            'content' => [
                'title' => fake()->sentence(3),
                'text' => fake()->sentence(10),
            ],
            'extra_data' => new NotificationMessageExtraData(),
            'send_after' => now(),
            'timeout' => 0,
            'sending_started_at' => null,
            'status' => NotificationDeliveryStatusEnum::CREATED,
            'log' => collect([
                '2021-01-01 18:00:00:   Send trial...',
                '2021-01-01 18:01:00:   Send trial...',
                '2021-01-01 18:02:00:   Response: 200 OK',
            ]),
        ];
    }
}
