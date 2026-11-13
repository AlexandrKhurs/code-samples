<?php

namespace App\Orchid\Fields;

use Orchid\Screen\Field;

class NotificationCascadeChannelCascadesArray extends Field
{
    protected $view = 'orchid.fields.notification-cascade-channel-cascades.array';

    protected $attributes = [
        'channels' => [],
    ];

    protected $inlineAttributes = [
    ];
}
