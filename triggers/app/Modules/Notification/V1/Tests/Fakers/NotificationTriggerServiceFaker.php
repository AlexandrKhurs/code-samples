<?php

namespace App\Modules\Notification\V1\Tests\Fakers;

use App\Modules\Notification\V1\NotificationTriggers\Trigger;
use App\Modules\Notification\V1\Services\NotificationTrigger\NotificationTriggerService;
use Mockery;
use PHPUnit\Framework\Assert;

class NotificationTriggerServiceFaker extends NotificationTriggerService
{
    private $firedTriggers = [];
    private $mockedTriggers = [];

    // trigger fire fake

    public function fire(Trigger $trigger): void
    {
        $this->firedTriggers[] = $trigger;
    }

    public function withTriggerClasses(array $triggerClasses): self
    {
        $this->availableNotificationTriggerClasses = $triggerClasses;
        return $this;
    }

    public function assertFired(
        Trigger|string $trigger,
        ?int $times = null,
        ?callable $filter = null
    ): void {
        $trigger = $this->getTrigger($trigger);
        $triggerClass = get_class($trigger);
        $triggerType = $trigger->type();

        $firedTriggers = array_filter($this->firedTriggers, function (Trigger $firedTrigger) use ($triggerClass, $filter) {
            if ($triggerClass !== get_class($firedTrigger)) {
                return false;
            }

            if ($filter && !$filter($firedTrigger)) {
                return false;
            }

            return true;
        });

        if (is_null($times) && empty($firedTriggers)) {
            Assert::fail('Trigger ' . $triggerType->value . ' was not fired, expected to be fired at least once');
            return;
        }

        if (!is_null($times) && count($firedTriggers) !== $times) {
            Assert::fail('Trigger ' . $triggerType->value . ' was fired ' . count($firedTriggers) . ' times, expected ' . $times . ' times');
            return;
        }

        Assert::assertTrue(true); // increase assertion count to avoid PHPUnit warning about no assertions
    }

    public function assertNotFired(
        Trigger|string $trigger,
        ?callable $filter = null
    ): void {
        $this->assertFired($trigger, 0, $filter);
    }

    // trigger mockery
    // TODO: refactor: seems it should go to Trigger, not to NotificationTriggerService,
    // but it'll require Trigger Facade, which seems too much for now

    public function mockTrigger(string $triggerClass, callable $callback)
    {
        $this->mockedTriggers[$triggerClass] = Mockery::mock($triggerClass, $callback);

        return $this->mockedTriggers[$triggerClass];
    }

    protected function getTriggerInstance(string $triggerClass): Trigger
    {
        return $this->mockedTriggers[$triggerClass] ?? $triggerClass::empty();
    }
}
