<?php

namespace App\Orchid\Requests\NotificationTemplate;

use App\Modules\Common\V1\Http\Requests\FormRequest;
use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\Notification\V1\Models\NotificationCascade;
use Illuminate\Validation\Rule;

class UpdateNotificationTemplateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'notification_template.name' => 'string|nullable',
            'notification_template.active' => 'required|boolean',
            'notification_template.trigger_type' => ['required', Rule::enum(NotificationTypeEnum::class)],
            'notification_template.recipients' => 'required|array',
            'notification_template.recipients_excluded' => 'array',
            'notification_template.filters' => 'array',
            'notification_template.unique_for_period' => 'required|integer|min:0',
            'notification_template.cascade_id' => ['required', Rule::exists(NotificationCascade::class, 'id')],
            'notification_template.content' => 'required|array',
            'notification_template.utm_campaign' => 'nullable|string',
            'notification_template.utm_content' => 'nullable|string',
        ];
    }
}
