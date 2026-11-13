<?php

declare(strict_types=1);

namespace App\Orchid\Screens\NotificationTemplate;

use App\Modules\Common\V1\Helpers\Type;
use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\Services\NotificationCascade\NotificationCascadeService;
use App\Modules\Notification\V1\Services\NotificationChannel\NotificationChannelService;
use App\Modules\Notification\V1\Services\NotificationTemplate\Dto\CreateNotificationTemplateDto;
use App\Modules\Notification\V1\Services\NotificationTemplate\NotificationTemplateService;
use App\Orchid\Layouts\NotificationTemplate\NotificationTemplateEditBasicLayout;
use App\Orchid\Layouts\NotificationTemplate\NotificationTemplateEditCascadeLayout;
use App\Orchid\Layouts\NotificationTemplate\NotificationTemplateEditCascadeShowLayout;
use App\Orchid\Layouts\NotificationTemplate\NotificationTemplateEditContentLayout;
use App\Orchid\Layouts\NotificationTemplate\NotificationTemplateEditFiltersLayout;
use App\Orchid\Requests\NotificationTemplate\CreateNotificationTemplateRequest;
use App\Orchid\Screens\NotificationTemplate\Traits\ConvertsNotificationTemplateData;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;

class NotificationTemplateCreateScreen extends Screen
{
    use ConvertsNotificationTemplateData;

    public function __construct(
        protected NotificationCascadeService $notificationCascadeService,
        protected NotificationChannelService $notificationChannelService,
    ) {
    }

    public function query(): iterable
    {
        $notificationTemplateData = old('notification_template', []);

        $trigger = NotificationTrigger::getTrigger($notificationTemplateData['trigger_type'] ?? null);

        return [
            'notification_template' => $notificationTemplateData,
            'allowed_recipients' => NotificationTrigger::getAllowedRecipients($trigger),
            'allowed_excluded_recipients' => NotificationTrigger::getAllowedExcludedRecipients($trigger),
            'recipient_descriptions' => $trigger?->recipientDescriptions() ?? [],
            'allowed_filters' => $this->allowedFiltersToOrchid($trigger?->filters() ?? []),
            'filter_descriptions' => $trigger?->filterDescriptions() ?? [],
            'allowed_variables' => NotificationTrigger::getAllowedVariables($trigger),
            'variable_descriptions' => $trigger?->variableDescriptions() ?? [],
            'allowed_content' =>
            $this->notificationCascadeService
                ->getChannelNames((int) ($notificationTemplateData['cascade_id'] ?? 0))
                ->mapWithKeys(fn(string $channelName) => [$channelName => $this->notificationChannelService->getAllowedContent($channelName)])
                ->toArray(),
        ];
    }

    public function layout(): iterable
    {
        return [
            NotificationTemplateEditBasicLayout::class,
            NotificationTemplateEditFiltersLayout::class,
            NotificationTemplateEditCascadeLayout::class,
            NotificationTemplateEditCascadeShowLayout::class,
            NotificationTemplateEditContentLayout::class,
        ];
    }

    public function name(): ?string
    {
        return 'Создание шаблона уведомления';
    }

    public function commandBar(): iterable
    {
        return [
            Button::make(__('Save'))
                ->icon('bs.check-circle')
                ->method('save'),
        ];
    }

    public function save(CreateNotificationTemplateRequest $request)
    {
        $notificationTemplateService = app(NotificationTemplateService::class);
        $notificationTemplate = $notificationTemplateService->create(new CreateNotificationTemplateDto(
            name: $request->validated('notification_template.name'),
            active: (bool)$request->validated('notification_template.active'),
            triggerType: NotificationTypeEnum::from($request->validated('notification_template.trigger_type')),
            filters: is_array($request->validated('notification_template.filters'))
                ? $this->filtersFromOrchid($request->validated('notification_template.filters'))
                : [],
            recipients: $request->validated('notification_template.recipients'),
            recipientsExcluded: is_array($request->validated('notification_template.recipients_excluded'))
                ? $request->validated('notification_template.recipients_excluded')
                : [],
            uniqueForPeriod: is_numeric($request->validated('notification_template.unique_for_period'))
                ? (int) $request->validated('notification_template.unique_for_period')
                : 0,
            cascadeId: is_numeric($request->validated('notification_template.cascade_id'))
                ? (int) $request->validated('notification_template.cascade_id')
                : Type::UNDEFINED,
            content: $request->validated('notification_template.content'),
            utmCampaign: $request->validated('notification_template.utm_campaign'),
            utmContent: $request->validated('notification_template.utm_content'),
        ));
        Toast::info('Шаблон уведомления создан');
        return redirect()->route('platform.notification.templates.edit', ['notificationTemplate' => $notificationTemplate->id]);
    }
}
