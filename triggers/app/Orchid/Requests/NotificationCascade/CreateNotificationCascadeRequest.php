<?php

namespace App\Orchid\Requests\NotificationCascade;

use App\Modules\Common\V1\Http\Requests\FormRequest;
use App\Modules\Common\V1\Rules\IsTimeRangeRule;
use App\Modules\Notification\V1\Rules\IsNotificationChannelCascadeRule;

class CreateNotificationCascadeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'notification_cascade.name' => 'required|string',
            'notification_cascade.silent_time_periods' => 'array',
            'notification_cascade.silent_time_periods.*' => [new IsTimeRangeRule(), 'required'],
            'notification_cascade.channel_cascades' => ['required', new IsNotificationChannelCascadeRule()],
        ];
    }
}
