<?php

namespace App\Modules\Notification\V1\Models;

use App\Modules\Common\V1\Models\Model;
use App\Modules\Notification\V1\Casts\AsNotificationMessageExtraData;
use App\Modules\Notification\V1\Enums\NotificationMessageStatusEnum;
use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\Notification\V1\Models\NotificationCascade;
use App\Modules\Notification\V1\Models\NotificationDelivery;
use App\Modules\Notification\V1\Models\NotificationTemplate;
use App\Modules\Notification\V1\ValueObjects\NotificationMessageExtraData;
use App\Modules\User\V1\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orchid\Filters\Types\Where;
use Orchid\Filters\Types\WhereDateStartEnd;

/**
 * @property-read int $id
 * @property int $recipient_id
 * @property User $recipient
 * @property int $template_id
 * @property NotificationTemplate $template
 * @property int $cascade_id
 * @property NotificationCascade $cascade
 * @property string $uuid
 * @property array $content // per-channel editable content to be sent
 * @property NotificationMessageExtraData|null $extra_data // common auto-generated data to be sent
 * @property NotificationTypeEnum $trigger_type
 * @property NotificationMessageStatusEnum $status
 * @property array|null $trigger_reconstruction_data // minimal essential data to reconstruct trigger instance (mostly for late relevance check before sending)
 * @property NotificationDelivery[]|\Illuminate\Database\Eloquent\Collection $deliveries
 * @property-read bool $is_new
 * @property-read bool $is_active
 * @property-read bool $is_created
 * @property-read bool $is_sending
 */
class NotificationMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'trigger_type',
        'recipient_id',
        'template_id',
        'cascade_id',
        'uuid',
        'content',
        'extra_data',
        'status',
        'trigger_reconstruction_data',
    ];

    protected $casts = [
        'trigger_type' => NotificationTypeEnum::class,
        'status' => NotificationMessageStatusEnum::class,
        'content' => 'array',
        'extra_data' => AsNotificationMessageExtraData::class,
        'trigger_reconstruction_data' => 'array',
    ];

    protected $allowedFilters = [
        'id' => Where::class,
        'created_at' => WhereDateStartEnd::class,
        'updated_at' => WhereDateStartEnd::class,
        'trigger_type' => Where::class,
        'template_id' => Where::class,
        'cascade_id' => Where::class,
        'recipient_id' => Where::class,
        'status' => Where::class,
    ];

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class);
    }

    public function cascade(): BelongsTo
    {
        return $this->belongsTo(NotificationCascade::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class, 'message_id');
    }

    public function getIsNewAttribute(): bool
    {
        return $this->status === NotificationMessageStatusEnum::CREATED;
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, NotificationMessageStatusEnum::activeStatuses());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', NotificationMessageStatusEnum::activeStatuses());
    }

    public function getIsCreatedAttribute(): bool
    {
        return $this->status === NotificationMessageStatusEnum::CREATED;
    }

    public function getIsSendingAttribute(): bool
    {
        return $this->status === NotificationMessageStatusEnum::SENDING;
    }
}
