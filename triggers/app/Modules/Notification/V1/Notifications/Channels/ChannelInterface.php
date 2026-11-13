<?php

namespace App\Modules\Notification\V1\Notifications\Channels;

use App\Modules\Notification\V1\Notifications\Notifiable\Notifiable;
use App\Modules\Notification\V1\Notifications\Notification;

interface ChannelInterface
{
    public function send(Notifiable $notifiable, Notification $notification): void;
}
