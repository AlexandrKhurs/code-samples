<?php

namespace App\Orchid\Screens\NotificationTemplate\Traits;

use Illuminate\Support\Collection;

trait ConvertsNotificationTemplateData
{
    /**
    * convert from model/dto representation to orchid data structure
    * so far dto's representation is [['event.type', 'in', [...]]]
    * orchid data structure is ['event.type' => [...]] for 'in' operator (only supported so far
    * @param array $filters model/dto representation
    * @return array orchid data structure
    */
    public function filtersToOrchid(Collection|array|null $filters): array
    {
        return collect($filters ?? [])
            ->mapWithKeys(function (array $filter) {
                [$filterKey, $operator, $values] = $filter;
                return [$this->encodeFilterKey($filterKey) => $values];
            })
            ->toArray();
    }

    /**
    * convert from orchid data structure to model/dto representation
    * see filtersToOrchid for details
    * @param array $filters orchid data structure
    * @return array model/dto representation
    */
    public function filtersFromOrchid(Collection|array|null $filters): array
    {
        return collect($filters ?? [])
            ->map(function (array $values, string $field) {
                return [$this->decodeFilterKey($field), 'in', $values];
            })
            ->values()
            ->toArray();
    }


    public function allowedFiltersToOrchid(Collection|array|null $allowedFilters): array
    {
        return collect($allowedFilters ?? [])
            ->mapWithKeys(function (array $allowedFilterOptions, string $filterKey) {
                return [$this->encodeFilterKey($filterKey) => $allowedFilterOptions];
            })
            ->toArray();
    }

    /** encode dots in filterKey to make it possible using keys like 'event.type' */
    private function encodeFilterKey(string $filterKey): string
    {
        return str_replace('.', '%%%DOT%%%', $filterKey);
    }

    /** decode dots in filterKey to make it possible using keys like 'event.type' */
    private function decodeFilterKey(string $filterKey): string
    {
        return str_replace('%%%DOT%%%', '.', $filterKey);
    }
}
