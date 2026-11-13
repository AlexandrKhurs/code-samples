<?php

namespace App\Modules\Notification\V1\Casts;

use App\Modules\Notification\V1\ValueObjects\NotificationMessageExtraData;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class AsNotificationMessageExtraData implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return NotificationMessageExtraData::fromArray(json_decode($value, associative: true) ?? []);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return json_encode($value?->toArray() ?? []);
    }
}
