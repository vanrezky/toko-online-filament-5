<?php

namespace App\Enums;

enum TransactionStatus: string
{

    case unpaid = "unpaid";
    case packed = "packed";
    case shipped = "shipped";
    case delivered = "delivered";
    case rejected = "rejected";
    case completed = "completed";
}
