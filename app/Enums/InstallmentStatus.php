<?php

namespace App\Enums;

enum InstallmentStatus: string
{
    case Active = "active";
    case Overdue = "overdue";
    case Completed = "completed";
    case Cancelled = "cancelled";
}
