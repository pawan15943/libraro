<?php

namespace App\Enums;

enum LearnerOperation: string
{
    case CREATE = 'create';
    case EDIT = 'edit';
    case RENEW = 'renew';
    case UPGRADE = 'upgrade';
    case CHANGE_PLAN = 'change_plan';
    case REACTIVE = 'reactive';

    case SWAP_SEAT = 'swap_seat';
    case CLOSE_SEAT = 'close_seat';
    case DELETE_SEAT = 'delete_seat';
    case FREEZE_SEAT = 'freeze_seat';

    case GIFT_DAYS = 'gift_days';
    case REFUND = 'refund';
    case MISC_PAYMENT = 'misc_payment';
}