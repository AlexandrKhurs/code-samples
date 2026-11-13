<?php

namespace App\Modules\Notification\V1\Providers;

use App\Modules\Notification\V1\Services\Sms\MessenteSmsService;
use App\Modules\Notification\V1\Services\Sms\RedSmsService;
use App\Modules\Notification\V1\Services\Sms\NullSmsService;
use App\Modules\Notification\V1\Services\Sms\SmsServiceInterface;
use App\Modules\Notification\V1\Services\Sms\StreamTelecomSmsService;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsServiceInterface::class, fn() => $this->prepareNotifier());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function prepareNotifier(): SmsServiceInterface
    {
        $type = config('services.sms.driver');

        switch ($type) {
            case 'none':
                return new NullSmsService();
            case 'redsms':
                $login = config('services.redsms.login');
                $apiKey = config('services.redsms.api_key');

                return new RedSmsService(
                    login: $login,
                    apiKey: $apiKey
                );
            case 'messente':
                $username = config('services.messente.username');
                $password = config('services.messente.password');

                return new MessenteSmsService(
                    username: $username,
                    password: $password,
                );
            case 'stream_telecom':
                $login = config('services.stream_telecom.login');
                $pass = config('services.stream_telecom.pass');
                $from = config('services.stream_telecom.from');

                return new StreamTelecomSmsService(
                    login: $login,
                    pass: $pass,
                    from: $from,
                );
            default:
                throw new InvalidArgumentException(sprintf(__('Unknown sms notifier <%s>.'), $type));
        }
    }
}
