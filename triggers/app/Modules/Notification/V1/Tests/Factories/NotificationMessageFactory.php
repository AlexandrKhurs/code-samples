<?php

namespace App\Modules\Notification\V1\Tests\Factories;

use App\Modules\Notification\V1\Enums\NotificationMessageStatusEnum;
use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\Models\NotificationCascade;
use App\Modules\Notification\V1\Models\NotificationMessage;
use App\Modules\Notification\V1\Models\NotificationTemplate;
use App\Modules\Notification\V1\Notifications\Channels\DatabaseChannel;
use App\Modules\Notification\V1\ValueObjects\NotificationMessageExtraData;
use App\Modules\User\V1\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationMessageFactory extends Factory
{
    protected $model = NotificationMessage::class;

    public function definition(): array
    {
        return [
            'trigger_type' => NotificationTrigger::getTriggerNotificationTypes()->first(),
            'recipient_id' => User::factory(),
            'template_id' => NotificationTemplate::factory(),
            'cascade_id' => NotificationCascade::factory(),
            'uuid' => fake()->uuid,
            'content' => [
                DatabaseChannel::NAME => [
                    'title' => fake()->sentence(3),
                    'text' => fake()->sentence(10),
                ],
            ],
            'extra_data' => new NotificationMessageExtraData(),
            'status' => NotificationMessageStatusEnum::CREATED,
        ];
    }
}
