<?php

namespace App\Modules\Notification\V1\Services;

use App\Modules\Notification\V1\Enums\NotificationDeliveryStatusEnum;
use App\Modules\Notification\V1\Notifications\Notifiable\NotifiableUser;
use App\Modules\Notification\V1\Notifications\Notification;

// TODO: тут подумать.. изначально задача была - объединить ВСЕ сервисы отпарвки и типизировать их возврат в перву очередь.
// (это надо было для единообразной обработки результата в трейте канлала)
// - либо НАСЛЕДОВАТЬ другие интерфейсы (типа SmsServiceInterface и т.п.) От этого...
// - либо вообще отказатсья от общего интерфейса, и просто допускать любой возврат
interface SenderServiceInterface
{
    public function send(NotifiableUser $notifiable, Notification $notification): NotificationDeliveryStatusEnum|bool|null;
}
