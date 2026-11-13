<?php

namespace App\Modules\Notification\V1\Tests\Factories;

use App\Modules\Common\V1\ValueObjects\TimeRange;
use App\Modules\Notification\V1\Models\NotificationCascade;
use App\Modules\Notification\V1\Notifications\Channels\DatabaseChannel;
use App\Modules\Notification\V1\Notifications\Channels\PushChannel;
use App\Modules\Notification\V1\Notifications\Channels\SmsChannel;
use App\Modules\Notification\V1\ValueObjects\NotificationChannelCascade;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationCascadeFactory extends Factory
{
    protected $model = NotificationCascade::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'silent_time_periods' => [],
            'channel_cascades' => [
                new NotificationChannelCascade(
                    channelName: DatabaseChannel::NAME,
                ),
            ],
        ];
    }
}
