<?php

namespace App\Modules\Notification\V1\Services\Sms;

use App\Modules\Notification\V1\Enums\NotificationDeliveryStatusEnum;
use App\Modules\Notification\V1\Notifications\Notifiable\NotifiablePhone;
use App\Modules\Notification\V1\Notifications\Notifiable\NotifiableUser;
use App\Modules\Notification\V1\Notifications\Notification;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;

class StreamTelecomSmsService implements SmsServiceInterface
{
    private string $url;

    public function __construct(
        private readonly string $login,
        private readonly string $pass,
        private readonly string $from,
    ) {
        $this->url = 'https://....';
    }

    public function send(NotifiableUser|NotifiablePhone $notifiable, Notification $notification): NotificationDeliveryStatusEnum|bool
    {
        $phones = [];

        switch ($notifiable::class) {
            case NotifiablePhone::class:
                $phones = $notifiable->getItems();
                break;
            case NotifiableUser::class:
                $users = $notifiable->getItems();
                foreach ($users as $user) {
                    $phones[] = $user->phone;
                }
                break;
            default:
                return NotificationDeliveryStatusEnum::SKIPPED;
                break;
        }

        $phones = array_filter($phones);

        if (empty($phones)) {
            return NotificationDeliveryStatusEnum::SKIPPED;
        }

        $errors = collect();
        foreach ($phones as $phone) {
            $response = Http::asForm()->post($this->url, [
                'login' => $this->login,
                'pass' => $this->pass,
                'sourceAddress' => $this->from,
                'destinationAddress' => $phone,
                'data' => $notification->toSms(),

            ]);

            if (!$response->ok()) {
                $errors->push($phone);
            }
        }

        if ($errors->count() > 0) {
            throw new Exception($errors);
        }

        return true;
    }
}
