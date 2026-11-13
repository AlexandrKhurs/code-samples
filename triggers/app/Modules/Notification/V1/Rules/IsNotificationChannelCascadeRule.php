<?php

namespace App\Modules\Notification\V1\Rules;

use App\Modules\Notification\V1\ValueObjects\NotificationChannelCascade;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;

class IsNotificationChannelCascadeRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail(':attribute должен быть массивом NotificationChannelCascade');
            return;
        }

        foreach ($value as $item) {
            if (!NotificationChannelCascade::isValidArray($item)) {
                $fail(':attribute должен быть массивом валидных NotificationChannelCascade');
                return;
            }
        }

        $channelNames = $this->extractChannelNames($value);
        $uniqueChannelNames = $channelNames->unique();
        $eachChannelNameIsUnique = $uniqueChannelNames->count() === $channelNames->count();

        if (!$eachChannelNameIsUnique) {
            $fail(':attribute может содержать каждый канал только один раз');
            return;
        }
    }

    private function extractChannelNames(array $cascades): Collection
    {
        return collect($cascades)
            ->map(fn(array $cascade) => $this->extractChannelNamesFromCascade($cascade))
            ->flatten();
    }

    private function extractChannelNamesFromCascade(array $cascade): Collection
    {
        return
            collect()
                ->push($cascade['channelName'])
                ->concat(!empty($cascade['onFail']) ? $this->extractChannelNamesFromCascade($cascade['onFail']) : collect())
                ->flatten();
    }
}
