<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum TransactionBillingStatus: string implements HasLabel, HasColor, HasIcon
{
    case not_applicable = 'not_applicable';
    case pending = 'pending';
    case submitted = 'submitted';
    case paid = 'paid';
    case failed = 'failed';
    case cancelled = 'cancelled';

    public function getLabel(): string|Htmlable|null
    {
        return ucfirst(__("admin/transaction-resource.billing_status.{$this->value}"));
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::not_applicable => 'gray',
            self::pending => 'warning',
            self::submitted => 'info',
            self::paid => 'success',
            self::failed => 'danger',
            self::cancelled => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::not_applicable => 'heroicon-o-minus-circle',
            self::pending => 'heroicon-o-clock',
            self::submitted => 'heroicon-o-paper-airplane',
            self::paid => 'heroicon-o-check-badge',
            self::failed => 'heroicon-o-x-circle',
            self::cancelled => 'heroicon-o-no-symbol',
        };
    }
}

