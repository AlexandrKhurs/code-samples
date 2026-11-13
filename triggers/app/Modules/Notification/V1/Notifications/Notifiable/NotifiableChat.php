<?php

namespace App\Modules\Notification\V1\Notifications\Notifiable;

/** @deprecated
 * it's a hardcoded way to route notifications to a specific TG-chats.. use NotificationTriggers's routing instead
 * (use NotificationTemplate recipient settings + notification's configurations (admin group) to route notifications to specific TG-chats)
 */
class NotifiableChat extends Notifiable
{
}
