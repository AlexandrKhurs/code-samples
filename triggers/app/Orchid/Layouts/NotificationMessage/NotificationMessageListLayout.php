<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\NotificationMessage;

use App\Modules\Common\V1\Helpers\HtmlHelper;
use App\Modules\Notification\V1\Enums\NotificationMessageStatusEnum;
use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\Notification\V1\Models\NotificationMessage;
use App\Modules\User\V1\Models\User;
use Illuminate\Support\Str;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\DateRange;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class NotificationMessageListLayout extends Table
{
    public $target = 'notification_messages';

    public function columns(): array
    {
        return [
            TD::make('id', 'ID')
                ->filter(Input::make()),
            TD::make('created_at', 'Created at')
                ->render(fn(NotificationMessage $notificationMessage) => $notificationMessage->created_at?->format('Y-m-d H:i:s'))
                ->filter(DateRange::make()),
            TD::make('updated_at', 'Updated at')
                ->render(fn(NotificationMessage $notificationMessage) => $notificationMessage->updated_at?->format('Y-m-d H:i:s'))
                ->filter(DateRange::make()),
            TD::make('trigger_type', 'Trigger')
                ->render(fn(NotificationMessage $notificationMessage) => $notificationMessage->trigger_type->value)
                ->filter(
                    Select::make()
                        ->options(
                            collect(NotificationTypeEnum::cases())
                                ->mapWithKeys(fn ($case) => [$case->value => $case->value])
                                ->all()
                        )
                        ->empty()
                ),
            TD::make('template_id', 'Template ID')
                ->render(fn(NotificationMessage $notificationMessage) => "#{$notificationMessage->template_id}")
                ->filter(),
            TD::make('recipient_id', 'Recipient')
                ->render(fn(NotificationMessage $notificationMessage) => "#{$notificationMessage->recipient_id} {$notificationMessage->recipient?->name}")
                ->filter(
                    Relation::make('filter[user_id]')
                        ->fromModel(User::class, 'firstname', 'id')
                        ->searchColumns('id', 'firstname', 'lastname', 'phone', 'email', 'telegram_username', 'telegram_id')
                        ->displayAppend('name_with_id')
                        ->empty()
                ),

            TD::make('excerpt', 'Excerpt')
                ->render(fn(NotificationMessage $notificationMessage) => $this->renderExcerpt($notificationMessage))
                ->filter(),

            TD::make('status')
                ->render(fn(NotificationMessage $notificationMessage) => $this->renderStatus($notificationMessage->status))
                ->filter(
                    Select::make()
                        ->fromEnum(NotificationMessageStatusEnum::class)
                        ->empty()
                ),

            TD::make('actions', 'Actions')
                ->render(fn(NotificationMessage $notificationMessage) =>
                    Link::make()
                        ->route('platform.notification.messages.show', ['notificationMessage' => $notificationMessage->id,])
                        ->name('View')
                        ->icon('bs.eye-fill')
                        ->render()),
        ];
    }

    private function renderStatus(NotificationMessageStatusEnum $status): string
    {
        return match (true) {
            $status->isSuccessful() => HtmlHelper::badgeSuccess($status->name),
            $status === NotificationMessageStatusEnum::SKIPPED => HtmlHelper::badgeSecondary($status->name),
            $status->isFailed() => HtmlHelper::badgeDanger($status->name),
            default => HtmlHelper::badgePrimary($status->name),
        };
    }

    private function renderExcerpt(NotificationMessage $notificationMessage): string
    {
        $excerptData = collect($notificationMessage->content)->first();
        $boldFields = ['title', 'header', 'subject'];

        $res = [];
        foreach ($excerptData as $key => $value) {
            if (in_array($key, $boldFields)) {
                $value = Str::limit($value, 25);
                $res[] = HtmlHelper::strong(HtmlHelper::nowrap($value));
            } else {
                $res[] = Str::limit($value, 40);
            }
        }
        return collect($res)->implode('<br>');
    }
}
