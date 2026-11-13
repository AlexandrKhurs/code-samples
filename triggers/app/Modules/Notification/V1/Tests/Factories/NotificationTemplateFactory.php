<?php

namespace App\Modules\Notification\V1\Tests\Factories;

use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\Models\NotificationCascade;
use App\Modules\Notification\V1\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationTemplateFactory extends Factory
{
    protected $model = NotificationTemplate::class;

    public function definition(): array
    {
        return [
            'active' => true,
            'trigger_type' => fn() => NotificationTrigger::getTriggerNotificationTypes()->first(),
            'filters' => [],
            'recipients' => [],
            'cascade_id' => NotificationCascade::factory(),
            'unique_for_period' => 0,
            'content' => [],
        ];
    }
}
