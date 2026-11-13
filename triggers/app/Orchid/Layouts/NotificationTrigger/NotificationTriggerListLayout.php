<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\NotificationTrigger;

use App\Modules\Common\V1\Helpers\HtmlHelper;
use App\Modules\Notification\V1\Enums\NotificationTriggerGroupEnum;
use App\Modules\Notification\V1\NotificationTriggers\Trigger;
use Illuminate\Support\Str;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class NotificationTriggerListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'notification_triggers';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('type', 'Trigger')
                ->render(fn(array $data) => $this->renderTriggerAndDescription($data['trigger'])),
            TD::make('group', 'Group')
                ->render(fn(array $data) => $this->renderGroup($data['trigger']->group())),
            TD::make('recipients', 'Recipients Available')
                ->render(fn(array $data) => HtmlHelper::nowrap(collect($data['available_recipients'])->implode("<br>"))),
            TD::make('templates', 'Templates overview')
                ->render(fn(array $data) =>
                    $this->renderTemplates(
                        collect($data['templates_configured'] ?? [])->toArray()
                    )),
            TD::make('actions', 'Actions')
                ->render(fn(array $data) =>
                    Link::make()
                        ->route('platform.notification.templates.list', ['filter[trigger_type]' => $data['trigger']->type()->value])
                        ->name('Go to templates')
                        ->icon('bs.caret-right-fill')
                        ->render()),
        ];
    }

    private function renderTriggerAndDescription(Trigger $trigger): string
    {
        $description = nl2br($trigger?->description() ?? '') ?: null;
        return collect([
            $trigger->type()->value,
            $description ? HtmlHelper::smallMuted($description) : '',
        ])->implode('<br>');
    }

    private function renderTemplates(array $templates): string
    {
        return collect($templates)
            ->map(fn(array $template) => $this->renderTemplate($template))
            ->implode("<br>");
    }

    private function renderTemplate(array $template): string
    {
        $recipients = collect($template['recipients'])->implode("<br>");
        $firstContent = collect($template['content'])->first() ?? [];
        $firstContentLine = Str::limit(collect($firstContent)->first() ?? '', 50);

        return "[{$recipients}]&nbsp;&nbsp;&nbsp;&nbsp;{$firstContentLine}";
    }

    private function renderGroup(NotificationTriggerGroupEnum $group): string
    {
        return match ($group) {
            NotificationTriggerGroupEnum::CRITICAL => HtmlHelper::badgeDanger($group->value),
            NotificationTriggerGroupEnum::MY_ACTIVITIES => HtmlHelper::badgePrimary($group->value),
            NotificationTriggerGroupEnum::NEWS => HtmlHelper::badgeSecondary($group->value),
            default => HtmlHelper::badgeSecondary($group->value),
        };
    }
}
