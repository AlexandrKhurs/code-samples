<?php

namespace App\Modules\Notification\V1\Services\NotificationTemplate;

use App\Modules\Common\V1\Helpers\FilterHelper;
use App\Modules\Common\V1\Helpers\TextHelper;
use App\Modules\Common\V1\Helpers\UrlHelper;
use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\Notification\V1\Models\NotificationTemplate;
use App\Modules\Notification\V1\Services\NotificationTemplate\Dto\CreateNotificationTemplateDto;
use App\Modules\Notification\V1\Services\NotificationTemplate\Dto\UpdateNotificationTemplateDto;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class NotificationTemplateService
{
    public const UTM_SOURCE = 'notifications';

    public function __construct(private readonly NotificationTemplateValidator $validator)
    {
    }

    public function create(CreateNotificationTemplateDto $dto): NotificationTemplate
    {
        $notificationTemplate = NotificationTemplate::create(FilterHelper::getDefinedData([
            'name' => $dto->name,
            'active' => $dto->active,
            'trigger_type' => $dto->triggerType,
            'filters' => $dto->filters,
            'recipients' => $dto->recipients,
            'recipients_excluded' => $dto->recipientsExcluded,
            'content' => $dto->content,
            'utm_campaign' => $dto->utmCampaign,
            'utm_content' => $dto->utmContent,
            'unique_for_period' => $dto->uniqueForPeriod,
            'cascade_id' => $dto->cascadeId,
        ]));

        $this->validate($notificationTemplate);

        return $notificationTemplate;
    }

    public function update(UpdateNotificationTemplateDto $dto): NotificationTemplate
    {
        $notificationTemplate = NotificationTemplate::findOrFail($dto->id);
        $notificationTemplate->update(FilterHelper::getDefinedData([
            'name' => $dto->name,
            'active' => $dto->active,
            'trigger_type' => $dto->triggerType,
            'filters' => $dto->filters,
            'recipients' => $dto->recipients,
            'recipients_excluded' => $dto->recipientsExcluded,
            'content' => $dto->content,
            'utm_campaign' => $dto->utmCampaign,
            'utm_content' => $dto->utmContent,
            'unique_for_period' => $dto->uniqueForPeriod,
            'cascade_id' => $dto->cascadeId,
        ]));

        $this->validate($notificationTemplate);

        return $notificationTemplate;
    }
    public function delete(int $id): void
    {
        NotificationTemplate::where('id', $id)->delete();
    }

    public function getActiveTemplatesByTrigger(NotificationTypeEnum $triggerType): EloquentCollection
    {
        return NotificationTemplate::query()
            ->where('active', true)
            ->where('trigger_type', $triggerType)
            ->get();
    }

    public function renderContent(
        array $templateContent,
        array|Collection $variableValues,
        string|null $utmCampaign = null,
        string|null $utmContent = null,
    ): array {
        $content = [];

        $variableValues = collect($variableValues)->mapWithKeys(fn($value, $key) => ["{{$key}}" => $value])->toArray();

        foreach ($templateContent as $channelName => $channelContent) {
            foreach ($channelContent as $contentType => $contentValue) {
                $renderedText = str_replace(
                    array_keys($variableValues),
                    array_values($variableValues),
                    $contentValue
                );

                $renderedText = $this->applyUtmFieldsToAllLinksInText(
                    text: $renderedText,
                    utmSource: self::UTM_SOURCE,
                    utmMedium: $channelName,
                    utmCampaign: $utmCampaign,
                    utmContent: $utmContent,
                );

                $content[$channelName][$contentType] = $renderedText;
            }
        }

        return $content;
    }

    protected function applyUtmFieldsToAllLinksInText(
        string $text,
        string|null $utmSource = null,
        string|null $utmMedium = null,
        string|null $utmCampaign = null,
        string|null $utmContent = null,
    ): string {
        $utmParams = array_filter([
            'utm_source' => $utmSource,
            'utm_medium' => $utmMedium,
            'utm_campaign' => $utmCampaign,
            'utm_content' => $utmContent,
        ]);
        return TextHelper::replaceHtmlLinkUrls($text, fn($url) => UrlHelper::addQueryParams($url, $utmParams));
    }

    private function validate(NotificationTemplate $notificationTemplate): void
    {
        $error = null;
        try {
            $this->validator->validate($notificationTemplate);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        $notificationTemplate->update([
            'validation_error' => $error,
        ]);
    }
}
