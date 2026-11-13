<?php

namespace App\Modules\Notification\V1\Enums;

enum NotificationTypeEnum: string
{
    case APP = 'app';
    case APP_ERROR = 'app.error';
    case NEWSLETTER = 'newsletter';
    case AUTH_VERIFICATION_CODE = 'auth.verification_code';
    case CHATS_DELETE = 'chats.delete';
    case CHATS_CREATE = 'chats.create';
    case MESSAGES_CREATE = 'messages.create';
    case MESSAGES_DELETE = 'messages.delete';
    case MESSAGES_READ = 'messages.read';
    case MESSAGES_UPDATE = 'messages.update';
    case MESSAGES_WRITE = 'messages.write';

    case EVENTS_CREATED = 'events.created';
    case EVENTS_CANCELED = 'events.canceled';
    case EVENTS_RESTORED = 'events.restored';
    case EVENTS_AVAILABLE_SLOT = 'events.available_slot';
    case EVENTS_UPCOMING = 'events.upcoming';
    case EVENTS_NOT_STARTED = 'events.not_started';
    case EVENTS_NOT_ENDED = 'events.not_ended';
    case EVENTS_FULL = 'events.full';
    case EVENTS_UPDATED = 'events.updated';
    case EVENTS_REGISTRATION_STARTED = 'events.registration_started';
    case EVENTS_FINISHED = 'events.finished';
    case EVENTS_COMPLETED = 'events.completed';
    case EVENTS_STARTED = 'events.started';
    case EVENTS_EXPIRED = 'events.expired';
    case EVENTS_ENDING = 'events.ending';
    case EVENTS_OWNER_CHANGED = 'events.owner_changed';

    case EVENTS_USER_JOINED = 'events.user.joined';
    case EVENTS_USER_PAID = 'events.user.paid';
    case EVENTS_USER_REJECTED_BY_LIMIT = 'events.user.rejected_by_limit';
    case EVENTS_JOIN_EXPIRED = 'events.join_expired';
    case EVENTS_USER_TRANSFER_COMPLETED = 'events.user.transfer.completed';
    case EVENTS_USER_KICKED = 'events.user.kicked';
    case EVENTS_USER_CANCELED = 'events.user.canceled';
    case EVENTS_USER_CONFIRMED = 'events.user.confirmed';
    case EVENTS_USER_SUBSCRIBED = 'events.user.subscribed';
    case EVENTS_USER_UNSUBSCRIBED = 'events.user.unsubscribed';
    case EVENTS_USER_APPLIED = 'events.user.applied';
    case EVENTS_USER_QUEUED = 'events.user.queued';
    case EVENTS_USER_INVITE_CREATED = 'events.user.invite.created';
    case EVENTS_USER_INVITE_CANCELED = 'events.user.invite.canceled';
    case EVENTS_USER_INVITE_EXPIRED = 'events.user.invite.expired';
    case EVENTS_USER_INVITE_CONFIRMED = 'events.user.invite.confirmed';
    case EVENTS_USER_INVITE_DECLINED = 'events.user.invite.declined';

    case EVENTS_TEAM_CREATED = 'events.teams.create';
    case EVENTS_TEAM_USER_JOINED = 'events.teams.user.joined'; // пользователь присоединился к команде (в теории - НЕ факт, что команда заполнена)
    case EVENTS_TEAM_JOIN_EXPIRED = 'events.teams.join_expired';
    case EVENTS_TEAM_COMPLETED = 'events.teams.completed'; // команда заполнена и вступила в турнир
    case EVENTS_TEAM_USER_REJECTED_BY_LIMIT = 'events.teams.user.rejected_by_limit';

    case PAYMENTS_CONSISTENCY_ERROR = 'payments.consistency_error';
}
