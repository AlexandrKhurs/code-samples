<?php

namespace App\Modules\Notification\V1\NotificationTriggers\Traits;

use App\Modules\Common\V1\Enums\ConfigurationGroupEnum;
use App\Modules\Configuration\V1\Services\Configuration\ConfigurationService;
use App\Modules\User\V1\Models\User;
use Illuminate\Support\Collection;

trait TriggerResolvesAdminRecipients
{
    public function adminRecipients(): array
    {
        return [
            'admin' => fn() => $this->resolveAdminRecipients(),
        ];
    }

    public function adminRecipientDescriptions(): array
    {
        return [
            'admin' => 'admin recipients (set in Configuration/Notifications)',
        ];
    }

    private function resolveAdminRecipients(): Collection
    {
        $adminRecipientIds = app(ConfigurationService::class)
            ->get(ConfigurationGroupEnum::NOTIFICATIONS, 'admin_recipient_ids');

        if (empty($adminRecipientIds)) {
            return collect();
        }

        return User::query()
            ->whereIn('id', explode(',', $adminRecipientIds))
            ->get();
    }
}
