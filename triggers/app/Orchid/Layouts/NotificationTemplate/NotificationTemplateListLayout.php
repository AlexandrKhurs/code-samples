<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\NotificationTemplate;

use App\Modules\Common\V1\Helpers\HtmlHelper;
use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\Models\NotificationCascade;
use App\Modules\Notification\V1\Models\NotificationTemplate;
use App\Modules\Notification\V1\Services\NotificationTemplate\NotificationTemplateValidator;
use Illuminate\Support\Str;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class NotificationTemplateListLayout extends Table
{
    public $target = 'notification_templates';

    public function compact(): bool
    {
        return true;
    }

    public function columns(): array
    {
        return [
            TD::make('id', 'ID')
                ->filter(),
            TD::make('name', 'Name'),
            TD::make('active', 'Active')
                ->render(fn(NotificationTemplate $notificationTemplate) =>
                $notificationTemplate->active ? __('✅') : __('⛔️'))
                ->filter(
                    Select::make()
                        ->options([
                            'active' => __('✅ Активный'),
                            'inactive' => __('⛔️ Неактивный'),
                        ])->empty(),
                ),
            TD::make('trigger_type', 'Trigger code')
                ->filter()
                ->render(fn(NotificationTemplate $notificationTemplate) => $this->renderTriggerTypeLink($notificationTemplate->trigger_type)),
            TD::make('cascade_id', 'Cascade')
                ->filter(
                    Select::make('notification_template.cascade_id')
                        ->fromModel(NotificationCascade::class, 'name', 'id')
                        ->empty()
                )
                ->render(fn(NotificationTemplate $notificationTemplate) =>
                $notificationTemplate->cascade?->name ?? ''),
            TD::make('template_filters', 'To/If')
                ->render(fn(NotificationTemplate $notificationTemplate) => collect([
                    collect([
                        $this->renderRecipients($notificationTemplate->recipients, 'To'),
                        $this->renderRecipients($notificationTemplate->recipients_excluded, 'who is not'),
                    ])->filter()->implode('<br>'),
                    $this->renderFilters($notificationTemplate->filters, $notificationTemplate->trigger_type, 'If'),
                ])->implode('<br><br>')),
            TD::make('content', 'Content')
                ->render(fn(NotificationTemplate $notificationTemplate) => collect([
                    $this->renderContentExcerpts($notificationTemplate->content),
                    $this->renderValidationErrors($notificationTemplate),
                ])->implode('<br>')),

            TD::make('actions', 'Actions')
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn(NotificationTemplate $notificationTemplate) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make(__('Edit'))
                            ->icon('bs.pencil-square')
                            ->route('platform.notification.templates.edit', ['notificationTemplate' => $notificationTemplate->id]),

                        Button::make(__('Delete'))
                            ->icon('bs.trash')
                            ->confirm('Точно удалить?')
                            ->method('remove', [
                                'id' => $notificationTemplate->id,
                            ]),
                    ])),
        ];
    }

    // TODO: rewrite after filters structure refactored (see Trigger::allowedFilters & Trigger::applyFilters)
    // only [['event.type', 'in', [...]]] is supported now
    private function renderFilters(array $filters, NotificationTypeEnum $triggerType, string $name): string
    {
        $res = HtmlHelper::strong($name) . ':<br>';

        if (empty($filters)) {
            return '';
        }

        $trigger = NotificationTrigger::getTrigger($triggerType);
        $allowedFilters = $trigger->filters();

        $res .= collect($filters)
            ->map(function (array $filter) use ($allowedFilters) {
                [$filterKey, $filterOperator, $filterValues] = $filter;
                $filterOptions = $allowedFilters[$filterKey];

                $res = $filterKey . ': ';

                $filterValues = !is_array($filterValues) ? [$filterValues] : $filterValues;
                $filterValues = collect($filterValues)
                    ->map(fn(string $v) => $filterOptions[$v])
                    ->filter()
                    ->map(fn(string $v) => strtolower($v))
                    ->map(fn(string $v) => "<br>- {$v}")
                    ->implode('');
                $res .= $filterValues . '<br>';

                return $res;
            })
            ->filter()
            ->implode('<br>');

        return $res;
    }

    private function renderContentExcerpts(array $content): string
    {
        return collect($content)
            ->map(fn(array $channelContent, string $channelName) =>
            HtmlHelper::strong(strtoupper($channelName)) . ':<br>'
                . $this->renderContentExcerpt($channelContent))
            ->map(fn(string $line) => "{$line}")
            ->implode('<br>');
    }

    private function renderContentExcerpt(array $channelContent): string
    {
        return collect($channelContent)
            ->map(fn(string|int|null $fieldContent, string $fieldName) =>
            match ($fieldName) {
                // 'title', 'subject', 'head', 'header' =>
                //     "<strong>" . Str::limit($fieldContent ?? '', 45) . "</strong>",
                default =>
                Str::limit(htmlspecialchars($fieldContent ?? ''), 50),
            })
            ->map(fn(string $line) => "&nbsp;&nbsp;&nbsp;&nbsp;{$line}")
            ->implode('<br>');
    }

    private function renderValidationErrors(NotificationTemplate $notificationTemplate): string
    {
        return
            $notificationTemplate->validation_error
            ? HtmlHelper::alertWarning('Validation errors: ' . Str::limit($notificationTemplate->validation_error, 30))
            : '';
    }

    private function renderRecipients(array $recipients, string $name): string
    {
        if (empty($recipients)) {
            return '';
        }

        return
            HtmlHelper::strong($name) . ':<br>'
            . collect($recipients)
            ->map(fn(string $recipient) => "- $recipient")
            ->implode('<br>');
    }

    private function renderTriggerTypeLink(NotificationTypeEnum $triggerType): string
    {
        $description = nl2br(NotificationTrigger::getTrigger($triggerType)?->description() ?? '') ?: null;
        return collect([
            Link::make($triggerType->value)
                ->icon('bs.funnel')
                ->route('platform.notification.templates.list', ['filter[trigger_type]' => $triggerType->value])
                ->toHtml(),
            $description ? HtmlHelper::smallMuted($description) : null,
        ])->filter()->implode('');
    }
}
