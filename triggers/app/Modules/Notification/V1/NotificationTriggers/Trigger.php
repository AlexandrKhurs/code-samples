<?php

namespace App\Modules\Notification\V1\NotificationTriggers;

use App\Modules\Notification\V1\Enums\NotificationTriggerGroupEnum;
use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\Notification\V1\Mappers\NotificationTriggerGroupMapper;
use App\Modules\Notification\V1\NotificationTriggers\Traits\TriggerResolvesAdminRecipients;
use App\Modules\Notification\V1\ValueObjects\NotificationMessageExtraData;
use App\Modules\User\V1\Models\User;
use Illuminate\Support\Collection;

abstract class Trigger
{
    use TriggerResolvesAdminRecipients;

    // TODO empty() - это архитектурный костыль, подумать как уйти от этого.
    // причина - методы типа filters() - они используются для получения списка кодов допустимых фильтров (это в идеале должен быть статический вызов,
    // т.к. для этого не нужно создавать Триггер с прикрученными к нему Евентом, Юзером и прочими данными),
    // но он же используется для получения resolver-коллбэков - а они должны создаваться в контексте конкретного Триггера.
    // в идеале, эти методы надо разнести, но так более громоздко (один метод - лаконичнее)...
    // короче, на подумать
    /** provide emtpy trigger instance (without any data attached) */
    public static function empty(): static
    {
        $constructor = (new \ReflectionClass(static::class))->getConstructor();
        $argsCount = $constructor?->getNumberOfParameters() ?? 0;

        return new static(...array_fill(0, $argsCount, null));
    }

    // non-static to use it like NotificationTrigger::getTrigger($triggerType)->make(...$args)
    public function make(...$args): static
    {
        return new static(...$args);
    }

    /** returns array of __construct() arguments to recreate trigger instance
     * @return array|null null means stillFired check is NOT supported
    */
    public function toReconstructionData(): array|null
    {
        return null;
    }

    abstract public function type(): NotificationTypeEnum;

    public function group(): NotificationTriggerGroupEnum
    {
        return NotificationTriggerGroupMapper::group($this->type());
    }

    public function description(): ?string
    {
        return null;
    }

    // TODO: refactor: "resolvable" should be VO with all possible types of resolvables
    // (like Resolvable::autoResolve(code), Resolvable::callable(fn()), ... which return subclass of Resolvable, with resolve() methods)
    /**
     * should return array of "resolvable" Users.
     * MUST return User or array|Collection of Users.
     * supports following formats:
     * - ['eventUser.user'] // auto-resolving values (resolved on demand using dot-notation; e.g. this is resolved as $this->eventUser->user)
     * - ['event.eventUsers'] // auto-resolving collections of users
     * - ['code' => fn() => 'value'] // callable that returns value or array/collection of values
     * - ['code' => 'value'] // direct value
     * - ['code' => ['value1', 'value2']] // direct array/collection of values
     * @return array|Collection<string, User>
     */
    public function recipients(): array
    {
        return [
            ...$this->adminRecipients(),
        ];
    }

    /**
     * should return array of recipient codes to exclude.
     * in most cases - any of "recipients()" can be excluded here, but it can be overridden if needed;
     * e.g. it's better to exclude HUGE lists of users from here (because excludedRecipients WAY LESS OPTIMIZED for huge amounts)
     * @return array of recipient codes to exclude
     */
    public function excludedRecipients(): array
    {
        return $this->recipients();
    }

    /** optional description for each recipient to be displayed in admin panel
     * return array [recipientCode => description] */
    public function recipientDescriptions(): array
    {
        return [
            ...$this->adminRecipientDescriptions(),
        ];
    }

    /**
     * return appliable filter for this trigger (only "IN" operation is supported so far)
     * TODO: think of better filters declaration structure (e.g. VO) for complex logic
     * @return array ['event.type' => [optionVal => optionName, ...]]
     */
    abstract public function filters(): array;

    /** optional description for each filter to be displayed in admin panel
     * return array [filterCode => description] */
    public function filterDescriptions(): array
    {
        return [];
    }

    /**
     * return array of "resolvable" variables for this trigger
     * supports following formats:
     * - ['event.manager.name'] // auto-resolving values (resolved on demand using dot-notation; e.g. this is resolved as $this->event->manager->name)
     * - ['user.languages'] // auto-resolving array of user languages
     * - ['code' => fn() => 'value'] // callable that returns value or array/collection of values
     * - ['code' => 'value'] // direct value
     * - ['code' => ['value1', 'value2']] // direct array/collection of values
     *
     * important: avoid 'recipient.*' names - it's reserved for particular recipient's fields
     * @return array|Collection<string, mixed>
     */
    public function variables(): array
    {
        return [
            'recipient.name' => null, // predefined variable, resolved in service using current recipient
            'url_base' => fn() => config('app.b2c_url'),
            'url_b2b_base' => fn() => config('app.b2b_url'),
        ];
    }

    /** optional description for each variable to be displayed in admin panel
     * return array [variableCode => description] */
    public function variableDescriptions(): array
    {
        return [
            'recipient.name' => 'recipient name (who received the notification, as it`s configured in "recipients" filter)',
            'url_base' => 'e.g. https://my-domain.ru for production',
            'url_b2b_base' => 'e.g. https://b2b.mydomain.ru for production',
        ];
    }

    /**
     * returns array of extra trigger-specific data to be sent with message
     * @return NotificationMessageExtraData
     */
    abstract public function extraData(): NotificationMessageExtraData|null;

    // TODO: think of better filters structure (e.g. VO) for complex logic
    /**
     * applies given filters and decide is should fire or skip this time
     * @param array $filters [['event.type', 'in', [...]]] is only supported now, empty means "no filtering, fire always"
     */
    abstract public function applyFilters(array $filters): bool;

    /**
     * late check if trigger conditions are still met (if supported)
     * to work properly, Trigger MUST:
     * - have toReconstructionData() method that returns array of data to recreate trigger instance
     * - optionally: have isStillFired() and firedStateTtl() methods overridden
     * @return bool
     **/
    public function isStillFired(): bool
    {
        return true;
    }

    public function supportsStillFiredCheck(): bool
    {
        return !is_null($this->toReconstructionData());
    }

    public function firedStateTtl(): int
    {
        return config('notification.fired_state_ttl');
    }

    /** empty data means NO idempotency protection */
    protected function uniqueIdData(): array
    {
        return [];
    }

    final public function uniqueId(): ?string
    {
        return
            empty($this->uniqueIdData())
            ? null
            : collect($this->uniqueIdData())
                ->map(fn($value, $key) => $key . ':' . $value)
                ->join(':');
    }

    public function uniqueIdTtl(): int
    {
        return config('notification.unique_id_ttl');
    }

    public function queue(): ?string
    {
        return null; // do not affect default queue mapping
    }
}
