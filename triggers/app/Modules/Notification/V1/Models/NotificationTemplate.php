<?php

namespace App\Modules\Notification\V1\Models;

use App\Modules\Common\V1\Models\Model;
use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\Notification\V1\Models\NotificationCascade;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Filters\Types\Ilike;
use Orchid\Filters\Types\Where;

/**
 * @property-read int $id
 * @property boolean $active
 * @property NotificationTypeEnum $trigger_type
 * @property array $filters so far it's [['event.type', 'in', [...]]] (empty array means ALL types).. later will think of VO or smth here
 * @property array $recipients
 * @property array $recipients_excluded
 * @property int $cascade_id
 * @property int $unique_for_period
 * @property array $content
 * @property string $utm_campaign
 * @property string $utm_content
 * @property NotificationCascade $cascade
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class NotificationTemplate extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'active',
        'trigger_type',
        'filters',
        'recipients',
        'recipients_excluded',
        'cascade_id',
        'unique_for_period',
        'content',
        'utm_campaign',
        'utm_content',
        'validation_error',
    ];

    protected $casts = [
        'active' => 'boolean',
        'trigger_type' => NotificationTypeEnum::class,
        'filters' => 'array', // TODO: make VO and cast for this later (when structure got clear)
        'recipients' => 'array',
        'recipients_excluded' => 'array',
        'content' => 'array',
    ];

    protected $allowedFilters = [
        'id' => Where::class,
        'active' => Where::class,
        'trigger_type' => Ilike::class,
        'cascade_id' => Where::class,
        // TODO:
        // 'filters' =>
        // 'recipients' =>
        // 'content' =>
    ];

    protected $allowedSorts = [
        'id',
    ];

    public function cascade(): BelongsTo
    {
        return $this->belongsTo(NotificationCascade::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
