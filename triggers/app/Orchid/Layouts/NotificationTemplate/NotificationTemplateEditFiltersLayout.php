<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\NotificationTemplate;

use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Orchid\Fields\CheckBoxGroup;
use App\Orchid\Screens\NotificationTemplate\Traits\ConvertsNotificationTemplateData;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Layouts\Listener;
use Orchid\Screen\Repository;
use Orchid\Support\Facades\Layout;

class NotificationTemplateEditFiltersLayout extends Listener
{
    use ConvertsNotificationTemplateData;

    protected $targets = ['notification_template.trigger_type'];

    public function layouts(): iterable
    {
        $triggerType = $this->query->get('notification_template.trigger_type');
        $trigger = NotificationTrigger::getTrigger($triggerType);

        $allowedRecipients = $this->query->get('allowed_recipients') ?? [];
        $allowedExcludedRecipients = $this->query->get('allowed_excluded_recipients') ?? [];
        $selectedRecipients = $this->query->get('notification_template.recipients') ?? [];
        $selectedRecipientsExcluded = $this->query->get('notification_template.recipients_excluded') ?? [];
        $recipientDescriptions = $this->query->get('recipient_descriptions') ?? [];

        $allowedFilters = $this->query->get('allowed_filters') ?? [];
        $selectedFilters = $this->query->get('notification_template.filters') ?? [];
        $filterDescriptions = $this->query->get('filter_descriptions') ?? [];

        $layouts = [];

        if ($trigger?->description()) {
            $layouts[] = Layout::rows([
                Label::make('Description')
                    ->title($trigger?->type()->value . ": " . $trigger?->description()),
            ]);
        }

        $layouts[] = Layout::columns([
            Layout::rows(
                $this->renderFilters('notification_template.filters', $allowedFilters, $selectedFilters, $filterDescriptions),
            ),
            Layout::rows([
                $this->renderRecipients(
                    name: 'notification_template.recipients',
                    title: 'Recipients',
                    allowedRecipients: $allowedRecipients,
                    selectedRecipients: $selectedRecipients,
                    recipientDescriptions: $recipientDescriptions,
                ),
                $this->renderRecipients(
                    name: 'notification_template.recipients_excluded',
                    title: 'Who is NOT',
                    allowedRecipients: $allowedExcludedRecipients,
                    selectedRecipients: $selectedRecipientsExcluded,
                    recipientDescriptions: $recipientDescriptions,
                    help: "Example: \n" .
                        "you have two different templates for ONE trigger (e.g. <strong>event.cancelled</strong>): \n" .
                        "- 1st text for <strong>users</strong> (all players) - 'manager cancelled match', \n" .
                        "- 2nd text for <strong>manager</strong> - 'you've cancelled match', \n" .
                        "BUT! if one of <strong>users</strong> is <strong>manager</strong> at the same time - he will get BOTH texts on event cancelled. \n" .
                        "to avoid this - configure 1st text's recipients as '<strong>users</strong> who is not <strong>manager</strong>'.",
                ),
            ]),
        ]);

        return $layouts;
    }

    public function renderFilters(string $name, array $allowedFilters, array $selectedFilters, array $filterDescriptions): array
    {
        // TODO: rewrite after filters structure refactored (see Trigger::allowedFilters & Trigger::applyFilters)
        // only "in" filters are supported so far
        $res = collect();

        $res->push(
            Label::make()
                ->title('Filters'),
        );

        $filters = collect($allowedFilters)
            ->map(function (array $allowedFilterOptions, string $filterKey) use ($name, $selectedFilters, $filterDescriptions) {
                $decodedFilterKey = $this->decodeFilterKey($filterKey);
                $filterDescription = $filterDescriptions[$decodedFilterKey] ?? null;
                $filterTitle =
                    $decodedFilterKey
                    . ($filterDescription ? ' - ' . $filterDescription : '');

                $selectedFilterValues = $selectedFilters[$filterKey] ?? [];

                return CheckBoxGroup::make($name . '.' . $filterKey)
                        ->title($filterTitle)
                        ->options($allowedFilterOptions)
                        ->value($selectedFilterValues);
            })->toArray();
        $res->push(...$filters);

        return $res->toArray();
    }

    public function renderRecipients(
        string $name,
        string $title,
        array|Collection $allowedRecipients,
        array|Collection $selectedRecipients,
        array|Collection $recipientDescriptions,
        ?string $help = null,
    ): CheckBoxGroup {
        $checkboxGroup = CheckBoxGroup::make($name)
            ->title($title)
            ->options(collect($allowedRecipients)
                ->mapWithKeys(fn($recipient) => [
                    $recipient => [
                        'label' => $recipient,
                        'description' => $recipientDescriptions[$recipient] ?? null,
                    ],
                ]))
            ->value($selectedRecipients);

        if ($help) {
            $checkboxGroup->help(nl2br($help));
        }

        return $checkboxGroup;
    }

    public function handle(Repository $repository, Request $request): Repository
    {
        $triggerType = NotificationTypeEnum::from($request->input('notification_template.trigger_type'));
        $trigger = NotificationTrigger::getTrigger($triggerType);

        $repository->set('notification_template.trigger_type', $triggerType);
        $repository->set('notification_template.recipients', $request->input('notification_template.recipients') ?? []);
        $repository->set('notification_template.recipients_excluded', $request->input('notification_template.recipients_excluded') ?? []);
        $repository->set('notification_template.filters', $request->input('notification_template.filters') ?? []);
        $repository->set('allowed_recipients', NotificationTrigger::getAllowedRecipients($trigger));
        $repository->set('allowed_excluded_recipients', NotificationTrigger::getAllowedExcludedRecipients($trigger));
        $repository->set('recipient_descriptions', $trigger?->recipientDescriptions() ?? []);
        $repository->set('allowed_filters', $this->allowedFiltersToOrchid(NotificationTrigger::getAllowedFilters($trigger)));
        $repository->set('filter_descriptions', $trigger?->filterDescriptions() ?? []);
        $repository->set('allowed_variables', NotificationTrigger::getAllowedVariables($trigger));
        $repository->set('variable_descriptions', $trigger?->variableDescriptions() ?? []);
        return $repository;
    }
}
