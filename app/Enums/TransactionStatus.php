<?php

namespace App\Enums;

enum TransactionStatus: string
{

    case unpaid = "unpaid";
    case packed = "packed";
    case in_transit = "in_transit";
    case shipped = "shipped";
    case delivered = "delivered";
    case picked_up = "picked_up";
    case rejected = "rejected";
    case cancelled = "cancelled";
    case completed = "completed";
}
