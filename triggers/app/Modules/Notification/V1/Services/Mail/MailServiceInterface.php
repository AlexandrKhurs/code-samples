<?php

namespace App\Modules\Notification\V1\Services\Mail;

use App\Modules\Notification\V1\Notifications\Notification;
use App\Modules\Notification\V1\Notifications\Notifiable\NotifiableEmail;
use App\Modules\Notification\V1\Notifications\Notifiable\NotifiableUser;

interface MailServiceInterface
{
    public function send(NotifiableUser|NotifiableEmail $notifiable, Notification $notification): bool;
}
