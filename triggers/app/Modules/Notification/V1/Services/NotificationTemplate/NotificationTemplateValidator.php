<?php

namespace App\Modules\Notification\V1\Services\NotificationTemplate;

use App\Modules\Notification\V1\Facades\NotificationTrigger;
use App\Modules\Notification\V1\Models\NotificationTemplate;
use App\Modules\Notification\V1\Services\NotificationChannel\NotificationChannelService;

class NotificationTemplateValidator
{
    public function __construct(
        protected NotificationChannelService $notificationChannelService,
    ) {
    }

    public function validate(NotificationTemplate $template): void
    {
        $this->validateRecipients($template);
        $this->validateContent($template);
        $this->validateFilters($template);
    }

    protected function validateFilters(NotificationTemplate $template): void
    {
        $allowedFilters = NotificationTrigger::getAllowedFilters($template->trigger_type->value)->toArray();

        foreach ($template->filters as $filter) {
            [$filterKey, $filterOperator, $filterValues] = $filter;

            if (!array_key_exists($filterKey, $allowedFilters)) {
                throw new \Exception("Filter key $filterKey is not allowed for trigger type $template->trigger_type->value");
            }

            foreach ($filterValues as $filterValue) {
                if (!array_key_exists($filterValue, $allowedFilters[$filterKey])) {
                    throw new \Exception("Filter value $filterValue is not allowed for filter key $filterKey");
                }
            }
        }
    }

    protected function validateRecipients(NotificationTemplate $template): void
    {
        $allowedRecipients = NotificationTrigger::getAllowedRecipients($template->trigger_type->value)->toArray();

        foreach ($template->recipients as $recipient) {
            if (!in_array($recipient, $allowedRecipients)) {
                throw new \Exception("Recipient $recipient is not allowed for trigger type {$template->trigger_type->value}.");
            }
        }
        foreach ($template->recipients_excluded ?? [] as $recipientExcluded) {
            if (!in_array($recipientExcluded, $allowedRecipients)) {
                throw new \Exception("Recipient excluded $recipientExcluded is not allowed for trigger type {$template->trigger_type->value}.");
            }
        }
    }

    protected function validateContent(NotificationTemplate $template): void
    {
        $triggerType = $template->trigger_type->value;
        $allowedVariables = collect(NotificationTrigger::getAllowedVariables($triggerType))
        ->map(fn($variable) => "{$variable}")
        ->toArray();

        foreach ($template->content as $channel => $content) {
            $allowedContent = collect($this->notificationChannelService->getAllowedContent($channel))->keys()->toArray();

            foreach ($content as $contentType => $contentValue) {
                // content type
                if (!in_array($contentType, $allowedContent)) {
                    throw new \Exception("Content type $contentType is not allowed for channel $channel");
                }

                // variables
                preg_match_all('/{(.*?)}/', $contentValue, $matches);
                $variables = $matches[1];
                foreach ($variables as $variable) {
                    if (!in_array($variable, $allowedVariables)) {
                        throw new \Exception("$channel.$contentType: variable {{$variable}} is not allowed for trigger type $triggerType");
                    }
                }
            }
        }
    }
}
