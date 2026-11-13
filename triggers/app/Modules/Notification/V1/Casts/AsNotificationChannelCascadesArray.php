<?php

namespace App\Modules\Notification\V1\Casts;

use App\Modules\Notification\V1\ValueObjects\NotificationChannelCascade;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class AsNotificationChannelCascadesArray implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return array_map(fn(array $item) => NotificationChannelCascade::fromArray($item), json_decode($value, associative: true) ?? []);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return json_encode(array_map(fn(NotificationChannelCascade $item) => $item->toArray(), $value));
    }
}
