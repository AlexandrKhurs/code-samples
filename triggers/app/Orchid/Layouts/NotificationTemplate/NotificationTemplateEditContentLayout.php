<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\NotificationTemplate;

use App\Modules\Notification\V1\Enums\NotificationEditableContentTypeEnum;
use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\Services\NotificationCascade\NotificationCascadeService;
use App\Modules\Notification\V1\Services\NotificationTemplate\NotificationTemplateService;
use App\Modules\Notification\V1\Services\NotificationChannel\NotificationChannelService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Listener;
use Orchid\Screen\Layouts\Rows;
use Orchid\Screen\Repository;
use Orchid\Support\Facades\Layout;

class NotificationTemplateEditContentLayout extends Listener
{
    public function __construct(
        protected NotificationCascadeService $notificationCascadeService,
        protected NotificationChannelService $notificationChannelService,
    ) {
    }

    protected $targets = [
        'notification_template.trigger_type',
        'notification_template.cascade_id'
    ];

    public function layouts(): iterable
    {
        $triggerType = $this->query->get('notification_template.trigger_type');
        $cascadeId = $this->query->get('notification_template.cascade_id');
        $renderContent = $triggerType && $cascadeId;
        if (!$renderContent) {
            return [];
        }

        $allowedContent = $this->query->get('allowed_content') ?? [];
        $filledContent = $this->query->get('notification_template.content') ?? [];
        $variableDescriptions = $this->query->get('variable_descriptions') ?? [];
        $allowedVariables = $this->query->get('allowed_variables') ?? [];

        return [
            ...collect($allowedContent)
                ->map(fn(array $channelContent, string $channelName) =>
                    $this->renderContent($channelName, $channelContent, $filledContent[$channelName] ?? []))
                ->toArray(),
            $this->renderUtmFields(),
            $this->renderVariables($allowedVariables, $variableDescriptions),
        ];
    }

    protected function renderContent(string $channelName, array $channelContent, array $filledContent)
    {
        return Layout::rows([
            ...collect($channelContent)
                ->map(fn($fieldType, $fieldName) =>
                    $this->renderContentField($channelName, $fieldName, $fieldType, $filledContent[$fieldName] ?? null))
                ->toArray()
        ])->title("$channelName content");
    }

    public function renderContentField(string $channelName, string $fieldName, NotificationEditableContentTypeEnum $fieldType, mixed $filledValue = null)
    {
        return match ($fieldType) {
            NotificationEditableContentTypeEnum::STRING =>
                Input::make($fieldName)
                    ->name('notification_template.content.' . $channelName . '.' . $fieldName)
                    ->title($fieldName)
                    ->value($filledValue),
            NotificationEditableContentTypeEnum::TEXT =>
                TextArea::make($fieldName)
                    ->name('notification_template.content.' . $channelName . '.' . $fieldName)
                    ->title($fieldName)
                    ->value($filledValue)
                    ->rows(10)
                    ->class('form-control'),
            default =>
                Label::make()
                    ->title($fieldName)
                    ->value('Unsupported field type: ' . $fieldType),
        };
    }

    public function renderUtmFields()
    {
        return Layout::rows([
            Input::make('notification_template.utm_source')
                ->name('notification_template.utm_source')
                ->title('UTM Source')
                ->value(NotificationTemplateService::UTM_SOURCE)
                ->disabled(),
            Input::make('notification_template.utm_medium')
                ->name('notification_template.utm_medium')
                ->title('UTM Medium')
                ->value('{channel_name}')
                ->disabled(),
            Input::make('notification_template.utm_campaign')
                ->name('notification_template.utm_campaign')
                ->title('UTM Campaign')
                ->value($this->query->get('notification_template.utm_campaign')),
            Input::make('notification_template.utm_content')
                ->name('notification_template.utm_content')
                ->title('UTM Content')
                ->value($this->query->get('notification_template.utm_content'))
                ->help('UTMs will be added to all links in texts on the fly. No need to specify them in texts manually.'),
        ]);
    }

    public function renderVariables(array|Collection $allowedVariables, array $variableDescriptions)
    {
        $labels = collect($allowedVariables)->map(function ($variable) use ($variableDescriptions) {
            $variableDescription = $variableDescriptions[$variable] ?? null;
            $variableTitle = "$variable" . ($variableDescription ? ' - ' . $variableDescription : '');

            return Label::make()
                ->value($variableTitle);
        })->toArray();

        $triggerType = $this->query->get('notification_template.trigger_type')?->value ?? 'any';
        return Layout::rows($labels)->title("Allowed variables for '$triggerType' trigger:");
    }

    public function handle(Repository $repository, Request $request): Repository
    {
        $triggerType = NotificationTypeEnum::tryFrom($request->input('notification_template.trigger_type') ?? '');
        $trigger = NotificationTrigger::getTrigger($triggerType);
        $cascadeId = $request->input('notification_template.cascade_id') ?? 0;

        $repository->set('notification_template.trigger_type', $triggerType);
        $repository->set('notification_template.cascade_id', $cascadeId);
        $repository->set('notification_template.content', $request->input('notification_template.content') ?? []);
        $repository->set(
            'allowed_content',
            $this->notificationCascadeService
                ->getChannelNames((int) $cascadeId)
                ->mapWithKeys(fn(string $channelName) => [$channelName => $this->notificationChannelService->getAllowedContent($channelName)])
                ->toArray(),
        );
        $repository->set('allowed_variables', NotificationTrigger::getAllowedVariables($triggerType));
        $repository->set('variable_descriptions', $trigger?->variableDescriptions() ?? []);
        return $repository;
    }
}
