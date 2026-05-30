<?php

namespace App\Enums;

enum EmailTemplateCode: string
{
    case RESET_PASSWORD = 'reset_password';
    case PAYMENT_REQUEST = 'payment_request';
    case PAYMENT_SUCCESS = 'payment_success';
    case ORDER_EXPIRY_REMINDER = 'order_expiry_reminder';
    case ORDER_EXPIRY = 'order_expiry';
    case ORDER_STATUS_CHANGED = 'order_status_changed';
    case NEWSLETTER = 'newsletter';
    case ORDER_THANK_YOU = 'order_thank_you';
}

