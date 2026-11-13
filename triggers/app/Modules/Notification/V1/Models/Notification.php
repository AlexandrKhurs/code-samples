<?php

namespace App\Modules\Notification\V1\Models;

use App\Modules\Common\V1\Models\Model;
use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\User\V1\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Notification extends Model
{
    use HasFactory;

    public const TYPE_FALLBACK = NotificationTypeEnum::APP;

    protected $fillable = [
        'uuid',
        'type',
        'title',
        'text',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    // enum cast with fallback value for invalid (legacy) values in db
    protected function type(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => NotificationTypeEnum::tryFrom($value) ?? self::TYPE_FALLBACK,
            set: fn ($value) => $value instanceof NotificationTypeEnum ? $value->value : $value,
        );
    }

    public function users(): BelongsToMany
    {
        return $this
            ->belongsToMany(User::class)
            ->withPivot('read_at');
    }
}
