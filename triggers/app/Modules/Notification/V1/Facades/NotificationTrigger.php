<?php

namespace App\Modules\Notification\V1\Facades;

use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\Notification\V1\NotificationTriggers\Trigger;
use App\Modules\Notification\V1\Services\NotificationMessage\NotificationMessageService;
use App\Modules\Notification\V1\Services\NotificationTemplate\NotificationTemplateService;
use App\Modules\Notification\V1\Services\NotificationTrigger\NotificationTriggerService;
use App\Modules\Notification\V1\Tests\Fakers\NotificationTriggerServiceFaker;
use App\Modules\User\V1\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Collection triggerClasses()
 * @method static Collection getTriggerNotificationTypes()
 * @method static Trigger getTrigger(NotificationTypeEnum|string|Trigger|null $trigger)
 * @method static bool isStillFired(NotificationTypeEnum|string|Trigger|null $trigger, array|null $triggerReconstructionData = null, array|null $filters = null)
 * @method static Collection getAllowedRecipients(NotificationTypeEnum|string|Trigger|null $trigger)
 * @method static Collection getAllowedExcludedRecipients(NotificationTypeEnum|string|Trigger|null $trigger)
 * @method static Collection getAllowedFilters(NotificationTypeEnum|string|Trigger|null $trigger)
 * @method static Collection getAllowedVariables(NotificationTypeEnum|string|Trigger|null $trigger)
 * @method static void fire(Trigger $trigger)
 * @method static void fireImmediately(Trigger $trigger)
 * @method static Collection resolveRecipients(NotificationTypeEnum|string|Trigger|null $trigger, Collection|array|null $filterRecipients = null, Collection|array|null $filterRecipientsExcluded = null)
 * @method static array resolveVariables(Trigger $trigger, User $recipient)
 * @method static array (?User $recipient = null)
 *
 * @method static void assertFired(Trigger|string $trigger, ?int $times = null, ?callable $filter = null)
 * @method static void assertNotFired(Trigger|string $trigger)
 */
class NotificationTrigger extends Facade
{
    protected static function getFacadeAccessor()
    {
        return NotificationTriggerService::class;
    }

    /**
     * @param array|null $availableNotificationTriggerClasses - only given triggers will be available if specified (all unspecified triggers will NOT exist)
     * @return NotificationTriggerServiceFaker
     */
    public static function fake(
        ?array $availableNotificationTriggerClasses = null,
    ): NotificationTriggerServiceFaker {
        $fake = new NotificationTriggerServiceFaker(
            availableNotificationTriggerClasses: $availableNotificationTriggerClasses ?? config('notification.triggers'),
            notificationTemplateService: app(NotificationTemplateService::class),
            notificationMessageService: app(NotificationMessageService::class),
        );

        app()->instance(NotificationTriggerService::class, $fake);

        return $fake;
    }
}
