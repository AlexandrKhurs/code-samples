<?php

namespace App\Modules\Notification\V1\NotificationTriggers\Traits;

use App\Modules\User\V1\Models\User;

/** adds $user variable, adds user to recipients and variables (with descriptions) to Trigger class */
trait TriggerHasUser
{
    public ?User $user = null;

    public function recipients(): array
    {
        return [
            ...parent::recipients(),
            'user',
        ];
    }

    public function recipientDescriptions(): array
    {
        return [
            ...parent::recipientDescriptions(),
            'user' => 'player who has joined/subscribed/cancelled/etc..',
        ];
    }

    public function variables(): array
    {
        return [
            ...parent::variables(),
            'user.name' => fn() => $this->user?->name,
        ];
    }

    public function variableDescriptions(): array
    {
        return [
            ...parent::variableDescriptions(),
            'user.name' => 'Player who has joined/subscribed/cancelled/etc..',
        ];
    }

    protected function uniqueIdData(): array
    {
        return [
            ...parent::uniqueIdData(),
            'user_id' => $this->user->id,
        ];
    }
}
