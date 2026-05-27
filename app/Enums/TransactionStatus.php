<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum TransactionStatus: string implements HasLabel, HasColor, HasIcon
{
    case packed = "packed";
    case in_transit = "in_transit";
    case shipped = "shipped";
    case delivered = "delivered";
    case picked_up = "picked_up";
    case cancelled = "cancelled";
    case completed = "completed";

    public function getLabel(): string|Htmlable|null
    {
        return ucfirst(__("admin/transaction-resource.status.{$this->value}"));
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::packed => 'warning',
            self::in_transit, self::shipped => 'primary',
            self::delivered, self::picked_up, self::completed => 'success',
            self::cancelled => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::packed => 'heroicon-o-archive-box',
            self::in_transit => 'heroicon-o-truck',
            self::shipped => 'heroicon-o-paper-airplane',
            self::delivered => 'heroicon-o-check-circle',
            self::picked_up => 'heroicon-o-hand-raised',
            self::cancelled => 'heroicon-o-minus-circle',
            self::completed => 'heroicon-o-check-badge',
        };
    }
}
