<?php

namespace App\Modules\Notification\V1\Models;

use App\Modules\Common\V1\Casts\AsTimeRangeArray;
use App\Modules\Common\V1\Models\Model;
use App\Modules\Notification\V1\Casts\AsNotificationChannelCascadesArray;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read int $id
 * @property string $name
 * @property array $silent_time_periods
 * @property array $channel_cascades
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class NotificationCascade extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'silent_time_periods',
        'channel_cascades',
    ];

    protected $casts = [
        'silent_time_periods' => AsTimeRangeArray::class . ':H:i:s',
        'channel_cascades' => AsNotificationChannelCascadesArray::class,
    ];
}
