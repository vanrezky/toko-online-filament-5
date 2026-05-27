<?php

namespace App\Enums;

enum InstallmentPaymentStatus: string
{
    case unpaid = 'unpaid';
    case partial = 'partial';
    case paid = 'paid';
    case overdue = 'overdue';
    case cancelled = 'cancelled';
}

