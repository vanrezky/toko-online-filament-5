<?php

namespace App\Enums;

enum InstallmentStatus: string
{
    case Active = "active";
    case Completed = "completed";
    case Overdue = "overdue";
    case defaulted = "defaulted";
    case Cancelled = "cancelled";
}
