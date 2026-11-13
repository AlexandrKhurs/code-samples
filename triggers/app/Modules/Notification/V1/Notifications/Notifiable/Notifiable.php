<?php

namespace App\Modules\Notification\V1\Notifications\Notifiable;

abstract class Notifiable
{
    private $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
