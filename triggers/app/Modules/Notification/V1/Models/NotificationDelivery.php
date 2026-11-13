<?php

namespace App\Modules\Notification\V1\Models;

use App\Modules\Common\V1\Models\Model;
use App\Modules\Notification\V1\Casts\AsNotificationMessageExtraData;
use App\Modules\Notification\V1\Enums\NotificationDeliveryStatusEnum;
use App\Modules\Notification\V1\ValueObjects\NotificationMessageExtraData;
use App\Modules\User\V1\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * @property-read int $id
 * @property int $message_id
 * @property string $channel_name
 * @property array $content
 * @property NotificationMessageExtraData|null $extra_data
 * @property \Carbon\Carbon $send_after
 * @property int $timeout seconds to expire while sending
 * @property \Carbon\Carbon $sending_started_at
 * @property NotificationDeliveryStatusEnum $status
 * @property Collection $log
 * @property NotificationMessage $message
 * @property-read bool $should_send_now
 * @property-read bool $sending_timeout_expired
 * @property-read bool $is_successful
 * @property-read bool $is_failed
 * @property-read bool $is_sending
 * @property-read bool $is_pending
 * @method static Builder sending()
 */
class NotificationDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'channel_name',
        'content',
        'extra_data',
        'send_after',
        'timeout',
        'log',
        'status',
        'sending_started_at',
    ];

    protected $casts = [
        'status' => NotificationDeliveryStatusEnum::class,
        'log' => AsCollection::class,
        'content' => 'array',
        'extra_data' => AsNotificationMessageExtraData::class,
        'send_after' => 'datetime',
        'sending_started_at' => 'datetime',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(NotificationMessage::class, 'message_id');
    }

    public function getRecipientAttribute(): ?User
    {
        return User::query()
            ->select('users.*')
            ->join('notification_messages', 'notification_messages.recipient_id', '=', 'users.id')
            ->where('notification_messages.id', $this->message_id)
            ->first();
    }

    public function getShouldSendNowAttribute(): bool
    {
        return $this->send_after && $this->send_after->isPast();
    }

    public function getSendingTimeoutExpiredAttribute(): bool
    {
        return
            $this->is_sending
            && !empty($this->sending_started_at)
            && !empty($this->timeout)
            && $this->sending_started_at->diffInSeconds(now()) > $this->timeout;
    }

    public function getIsSuccessfulAttribute(): bool
    {
        return in_array($this->status, NotificationDeliveryStatusEnum::successfulStatuses());
    }

    public function getIsFailedAttribute(): bool
    {
        return in_array($this->status, NotificationDeliveryStatusEnum::failedStatuses());
    }

    public function getIsSendingAttribute(): bool
    {
        return $this->status === NotificationDeliveryStatusEnum::SENDING;
    }

    public function scopeSending(Builder $query): Builder
    {
        return $query->where('status', NotificationDeliveryStatusEnum::SENDING);
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, NotificationDeliveryStatusEnum::activeStatuses());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', NotificationDeliveryStatusEnum::activeStatuses());
    }

    public function getIsPendingAttribute(): bool
    {
        return in_array($this->status, NotificationDeliveryStatusEnum::pendingStatuses());
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', NotificationDeliveryStatusEnum::pendingStatuses());
    }
}
