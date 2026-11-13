<?php

namespace App\Modules\Notification\V1\Tests\Integration\Triggers;

use App\Modules\Common\V1\Tests\TestCase;
use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\Mappers\NotificationTriggerGroupMapper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TriggersConsistencyTest extends TestCase
{
    use DatabaseTransactions;

    public function testAllTriggerClassesExistInNamespace()
    {
        $triggerClasses = $this->getAllTriggerClassesFromExistingPhpFiles();

        foreach ($triggerClasses as $triggerClass) {
            $this->assertTrue(class_exists($triggerClass));
        }
    }

    public function testAllTriggersAreRegisteredInConfig()
    {
        $triggerClasses = $this->getAllTriggerClassesFromExistingPhpFiles();

        foreach ($triggerClasses as $triggerClass) {
            $this->assertTrue(
                collect(config('notification.triggers'))->contains($triggerClass),
                "Trigger {$triggerClass} is not registered in config"
            );
        }
    }

    public function testAllTriggerClassesAreVisibleToNotificationTriggerService()
    {
        $triggerClasses = $this->getAllTriggerClassesFromExistingPhpFiles();

        foreach ($triggerClasses as $triggerClass) {
            $this->assertContains(
                $triggerClass,
                NotificationTrigger::triggerClasses()->toArray(),
                "Trigger {$triggerClass} is not accessible by NotificationTriggerService"
            );
        }
    }


    public function testAllTriggersAreRetrievableByType()
    {
        $triggerClasses = $this->getAllTriggerClassesFromExistingPhpFiles();

        foreach ($triggerClasses as $triggerClass) {
            $notificationType = $triggerClass::empty()->type();

            $this->assertNotNull(
                $notificationType,
                "Trigger {$triggerClass} has no notification type",
            );
            $this->assertNotNull(
                NotificationTrigger::getTrigger($notificationType),
                "Trigger {$triggerClass} is not retrievable by type {$notificationType->value}"
            );
        }
    }

    public function testAllTriggersHaveUniqueTypes()
    {
        $triggerClasses = $this->getAllTriggerClassesFromExistingPhpFiles();

        $triggerTypes = collect($triggerClasses)
            ->map(fn($triggerClass) => $triggerClass::empty()->type())
            ->toArray();

        $triggerTypeDuplicates = collect($triggerTypes)
            ->duplicates()
            ->map(fn($triggerType) => $triggerType->value);

        $this->assertEmpty(
            $triggerTypeDuplicates->toArray(),
            'Trigger type duplicates found for: ' . $triggerTypeDuplicates->implode(', ')
        );
    }

    public function testAllTriggerTypesAreMappedToGroups()
    {
        $triggerClasses = $this->getAllTriggerClassesFromExistingPhpFiles();
        foreach ($triggerClasses as $triggerClass) {
            $notificationType = $triggerClass::empty()->type();
            $group = NotificationTriggerGroupMapper::group($notificationType);
            $this->assertNotNull($group, "Trigger type {$notificationType->value} is not mapped to any group");
        }
    }

    private function getAllTriggerClassesFromExistingPhpFiles(): array
    {
        $triggerFiles = File::files(
            app_path('Modules/Notification/V1/NotificationTriggers/*')
        );

        return collect($triggerFiles)
            ->filter(function ($file) {
                if (Str::contains($file->getPath(), 'Trait', ignoreCase: true)) {
                    return false;
                }

                if (Str::contains($file->getFilename(), 'Base', ignoreCase: true)) {
                    return false;
                }

                return true;
            })
            ->map(function ($file) {
                $relativePath = Str::replaceFirst(app_path(), '', $file->getPath());
                $name = $file->getFilenameWithoutExtension();
                return 'App' . Str::replace('/', '\\', $relativePath) . '\\' . $name;
            })
            ->toArray();
    }
}
