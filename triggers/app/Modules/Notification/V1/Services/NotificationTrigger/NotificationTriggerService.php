<?php

namespace App\Modules\Notification\V1\Services\NotificationTrigger;

use App\Modules\Common\V1\Models\Model;
use App\Modules\Notification\V1\Enums\NotificationTypeEnum;
use App\Modules\Notification\V1\Jobs\FireNotificationTriggerJob;
use App\Modules\Notification\V1\Models\NotificationTemplate;
use App\Modules\Notification\V1\NotificationTriggers\Trigger;
use App\Modules\Notification\V1\Services\NotificationMessage\Dto\CreateNotificationMessageDto;
use App\Modules\Notification\V1\Services\NotificationMessage\NotificationMessageService;
use App\Modules\Notification\V1\Services\NotificationTemplate\NotificationTemplateService;
use App\Modules\User\V1\Mappers\UserRoleMapper;
use App\Modules\User\V1\Models\User;
use Generator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Traversable;

class NotificationTriggerService
{
    protected const AUTO_RESOLVE = 'AUTO_RESOLVE'; // TODO: use smth more unique to avoid collisions

    public function __construct(
        protected array $availableNotificationTriggerClasses,
        protected NotificationTemplateService $notificationTemplateService,
        protected NotificationMessageService $notificationMessageService,
    ) {
    }

    public function triggerClasses(): Collection
    {
        return collect($this->availableNotificationTriggerClasses);
    }

    public function getTriggerNotificationTypes(): Collection
    {
        return collect($this->availableNotificationTriggerClasses)
            ->map(fn(string $class) => $this->getTriggerInstance($class)->type());
    }

    public function getTrigger(NotificationTypeEnum|string|Trigger|null $trigger): Trigger|null
    {
        if (is_null($trigger)) {
            return null;
        }

        if ($trigger instanceof Trigger) {
            return $trigger;
        }

        if ($trigger instanceof NotificationTypeEnum) {
            return $this->getTriggerByType($trigger);
        }

        if (is_string($trigger)) {
            if (class_exists($trigger) && is_subclass_of($trigger, Trigger::class)) {
                return $this->getTriggerInstance($trigger);
            }

            return $this->getTriggerByType(NotificationTypeEnum::tryFrom($trigger));
        }

        return null;
    }

    // TODO: refactor: seems it should go to Trigger, not to NotificationTriggerService,
    // but it'll require Trigger Facade (to make it possible to mock triggers properly), which seems too much for now
    protected function getTriggerInstance(string $triggerClass): Trigger
    {
        return $triggerClass::empty();
    }

    public function fire(Trigger $trigger): void
    {
        Log::info('NotificationTriggerService::fire(): TRIGGER: ' . $trigger->type()->value . ' uniqueId: ' . $trigger->uniqueId());

        dispatch(
            app(FireNotificationTriggerJob::class, [
                'trigger' => $trigger,
            ])
        );
    }

    public function fireImmediately(Trigger $trigger): void
    {
        Log::info('NotificationTriggerService::fireImmediately(): trigger: ' . $trigger->type()->value . ' uniqueId: ' . $trigger->uniqueId());

        if ($this->isIdempotencyViolated($trigger)) {
            Log::info('trigger IGNORED due to idempotency violation');
            return;
        }

        // templates to generate messages (active, for this trigger, filtered by template's filters)
        $templates = $this->notificationTemplateService
            ->getActiveTemplatesByTrigger($trigger->type())
            ->filter(fn(NotificationTemplate $template) => $trigger->applyFilters($template->filters));

        foreach ($templates as $template) {
            /** @var NotificationTemplate $template */
            $recipients = $this->resolveRecipients($trigger, $template->recipients, $template->recipients_excluded);

            foreach ($recipients as $recipient) {
                // TODO: think of content pre-analysis to find WHAT vars are needed (to avoid resolving all vars as they might be heavy to calc in future)
                $content = $this->notificationTemplateService->renderContent(
                    templateContent: $template->content,
                    variableValues: $this->resolveVariables($trigger, $recipient),
                    utmCampaign: $template->utm_campaign,
                    utmContent: $template->utm_content,
                );

                $this->notificationMessageService->create(new CreateNotificationMessageDto(
                    triggerType: $trigger->type(),
                    templateId: $template->id,
                    recipientId: $recipient->id,
                    cascadeId: $template->cascade_id,
                    content: $content,
                    extraData: $trigger->extraData(),
                    triggerReconstructionData: $trigger->toReconstructionData(),
                ));

                if ($trigger->supportsStillFiredCheck()) {
                    // cache fired=true to avoid early isStillFired() re-check when sending just after firing trigger
                    $this->putCachedFiredState($trigger, $trigger->toReconstructionData(), true);
                }
            }
        }
    }

    public function getAllowedRecipients(NotificationTypeEnum|string|Trigger|null $trigger): Collection
    {
        $trigger = $this->getTrigger($trigger);

        return collect($this->normalizeResolvables($trigger?->recipients() ?? []))
            ->keys();
    }

    public function getAllowedExcludedRecipients(NotificationTypeEnum|string|Trigger|null $trigger): Collection
    {
        $trigger = $this->getTrigger($trigger);

        return collect($this->normalizeResolvables($trigger?->excludedRecipients() ?? []))
            ->keys();
    }

    public function getAllowedFilters(NotificationTypeEnum|string|Trigger|null $trigger): Collection
    {
        $trigger = $this->getTrigger($trigger);

        return collect($trigger?->filters() ?? []);
    }

    public function getAllowedVariables(NotificationTypeEnum|string|Trigger|null $trigger): Collection
    {
        $trigger = $this->getTrigger($trigger);

        $recipientVariables = collect($this->normalizeResolvables($this->getPredefinedVariables() ?? []));
        $triggerVariables = collect($this->normalizeResolvables($trigger?->variables() ?? []));

        return
            $recipientVariables
            ->merge($triggerVariables)
            ->keys();
    }

    // TODO: refactor: make it protected (but think about how to test it first)
    // TODO: refactor: simplify logic a bit (hard to read so far)
    public function resolveRecipients(
        NotificationTypeEnum|string|Trigger|null $trigger,
        array|null $filterRecipients,
        array|null $filterRecipientsExcluded,
    ): Generator {
        $trigger = $this->getTrigger($trigger);
        $normalizedAllowedExcludedRecipients = $this->normalizeResolvables($trigger?->excludedRecipients() ?? []);
        $normalizedAllowedRecipients = $this->normalizeResolvables($trigger?->recipients() ?? []);

        // resolve excluded recipients
        $excludedRecipientIds = [];
        foreach ($normalizedAllowedExcludedRecipients as $recipientCode => $resolver) {
            if (!in_array($recipientCode, $filterRecipientsExcluded ?? [])) {
                continue;
            }

            $resolved = $this->resolveResolvable($recipientCode, $resolver, $trigger);

            // resolved = single recipient here, pack into array<User> for further processing
            if (!is_array($resolved) && !($resolved instanceof Traversable)) {
                $resolved = [$resolved];
            }

            // get IDs from multiple resolved recipients (resolved = array or any Traversable, incl. LazyCollections or other Generators)
            foreach ($resolved as $recipient) {
                if (!empty($recipient?->id)) {
                    $excludedRecipientIds[] = $recipient->id;
                }
            }
        }

        // resolve and yield recipients
        $yieldedRecipientIdsHash = []; // one recipient may be resolved by several recipient codes
        foreach ($normalizedAllowedRecipients as $recipientCode => $resolver) {
            if (!in_array($recipientCode, $filterRecipients ?? [])) {
                continue;
            }

            $resolved = $this->resolveResolvable($recipientCode, $resolver, $trigger);

            // if resolved = single User here, pack into array<User> for further processing
            if (!is_array($resolved) && !($resolved instanceof Traversable)) {
                $resolved = [$resolved];
            }

            // yield resolved recipients from resolved, filtered by NPC, excludedRecipientIds and already yielded ones (resoleved = array|Traversable<User>)
            // (chunk them to avoid N+1 Rolesquery on mass sending.. and keep in mind that $resolved CAN be a generator)
            $resolvedLazy = LazyCollection::make(function () use ($resolved) {
                yield from $resolved;
            });

            foreach ($resolvedLazy->chunk(100) as $recipientsChunk) {
                $npcRecipientIdsHash = User::query() // NPC users (bots, system) are never notified
                    ->whereIn('id', $recipientsChunk->pluck('id')->filter())
                    ->whereHas('roles', fn($query) => $query->whereIn('slug', UserRoleMapper::npcRoles()))
                    ->pluck('id')
                    ->flip()
                    ->toArray();

                foreach ($recipientsChunk as $recipient) {
                    if (
                        !empty($recipient)
                        && !isset($npcRecipientIdsHash[$recipient->id])
                        && !in_array($recipient->id, $excludedRecipientIds)
                        && !isset($yieldedRecipientIdsHash[$recipient->id])
                    ) {
                        $yieldedRecipientIdsHash[$recipient->id] = true;
                        yield $recipient;
                    }
                }
            }
        }
    }

    // TODO: refactor: make it protected (but think about how to test it first)
    public function resolveVariables(Trigger $trigger, User $recipient): array
    {
        $allResolvableVariables = array_merge(
            $this->getPredefinedVariables($recipient),
            $trigger->variables(),
        );

        return $this->normalizeResolvables($allResolvableVariables)
            ->map(fn($resolver, $code) => $this->resolveResolvable($code, $resolver, $trigger))
            ->toArray();
    }

    /** variables related to recipient, not trigger
     * @return array<string, mixed>  array or resolvables (as in Trigger::variables() describe) (but MUST NOT be auto-resolvable, 'cause triggger do NOT know about recipient) */
    protected function getPredefinedVariables(?User $recipient = null): array
    {
        return [
            'recipient.name' => fn() => $recipient?->name,
            // TODO: add more recipient's vars here
        ];
    }

    protected function getTriggerByType(?NotificationTypeEnum $triggerType): Trigger|null
    {
        if (is_null($triggerType)) {
            return null;
        }

        $triggerClass = collect($this->availableNotificationTriggerClasses)
            ->first(fn(string $class) => $this->getTriggerInstance($class)->type() === $triggerType);

        return $triggerClass ? $this->getTriggerInstance($triggerClass) : null;
    }

    /** normalize resolvables from multi-format (e.g. see Trigger::recipients) to array of [code => resolver] */
    protected function normalizeResolvables(Collection|array|null $resolvables): Collection
    {
        return collect($resolvables ?? [])
            ->mapWithKeys(fn($value, $key) =>
                is_numeric($key)
                    ? [$value => self::AUTO_RESOLVE]
                    : [$key => $value]);
    }

    /** hint: can return Generator as well! (at least for recipitents)
     * @return Model|Model[]|Generator|Traversable
    */
    protected function resolveResolvable(string $code, mixed $resolver, NotificationTypeEnum|string|Trigger|null $trigger): mixed
    {
        $trigger = $this->getTrigger($trigger);

        // resolve raw val
        if ($resolver === self::AUTO_RESOLVE) {
            $value = data_get($trigger, $code); // auto resolve dot-notation code (e.g. 'eventUser.user.name' to $this->eventUser->user->name value)
        } elseif (is_callable($resolver)) {
            $value = $resolver(); // resolve using callback
        } else {
            $value = $resolver; // use direct value
        }

        return $value;
    }

    // TODO: implemented with cache so far.. think of DB implementation later
    protected function isIdempotencyViolated(Trigger $trigger): bool
    {
        $uniqueId = $trigger->uniqueId();
        if (empty($uniqueId)) {
            return false;
        }

        $cacheKey = 'notification_triger_unique_id:' . $uniqueId;
        $isIdempotencyViolated = Cache::has($cacheKey);

        // refresh key ttl anyway
        Cache::put($cacheKey, true, $trigger->uniqueIdTtl());

        return $isIdempotencyViolated;
    }

    public function isStillFired(
        NotificationTypeEnum|string|Trigger|null $trigger,
        array|null $triggerReconstructionData = null,
        array|null $filters = null,
    ): bool {
        $trigger = $this->getTrigger($trigger);
        if (is_null($trigger)) {
            return false; // some error, abort sending
        }

        if (!$trigger->supportsStillFiredCheck() || is_null($triggerReconstructionData)) {
            return true; // no late check support - assume trigger is forever relevant after it's fired
        }

        $isStillFired = $this->getCachedFiredState($trigger, $triggerReconstructionData);
        if (is_null($isStillFired)) {
            $trigger = $trigger->make(...$triggerReconstructionData);

            $isStillFired =
                $trigger->isStillFired()
                && $trigger->applyFilters($filters ?? []); // in case of later change of objects related to trigger (event, user, ..) and make it not match filters anymore

            $this->putCachedFiredState($trigger, $triggerReconstructionData, $isStillFired);
        }

        return $isStillFired;
    }

    private function firedStateCacheKey(Trigger $trigger, array $triggerReconstructionData): string
    {
        // we CAN NOT use uniqueId() here, trigger is empty for now (before it's reconstructed)
        return 'notification_trigger_fired_state:' . $trigger->type()->value . ':' . md5(json_encode($triggerReconstructionData) ?? '');
    }

    private function putCachedFiredState(Trigger $trigger, array $triggerReconstructionData, bool $isStillFired): void
    {
        Cache::put($this->firedStateCacheKey($trigger, $triggerReconstructionData), $isStillFired, $trigger->firedStateTtl());
    }

    private function getCachedFiredState(Trigger $trigger, array $triggerReconstructionData): bool|null
    {
        return Cache::get($this->firedStateCacheKey($trigger, $triggerReconstructionData), null);
    }
}
