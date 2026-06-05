<?php

namespace App\Services;

use App\Models\Customer;
use App\Settings\GeneralSettings;

class CreditLimitService
{
    public function __construct(
        protected GeneralSettings $generalSettings
    ) {}

    public function shouldEnforceLimit(): bool
    {
        return (bool) ($this->generalSettings->enforce_credit_limit ?? true);
    }

    public function getEffectiveLimit(Customer $customer): float
    {
        return $customer->effective_credit_limit;
    }

    public function getOutstandingBalance(Customer $customer): float
    {
        return $customer->outstanding_balance;
    }

    public function getRemainingLimit(Customer $customer): float
    {
        return $customer->remaining_credit_limit;
    }

    public function canCreateInstallment(Customer $customer, float $amount): bool
    {
        if (! $this->shouldEnforceLimit()) {
            return true;
        }

        return $customer->canCreateInstallment($amount);
    }

    public function canCreateFullBilling(Customer $customer, float $amount): bool
    {
        if (! $this->shouldEnforceLimit()) {
            return true;
        }

        return $customer->remaining_credit_limit >= $amount;
    }

    public function validateCheckout(Customer $customer, float $totalAmount, string $paymentType): bool
    {
        if ($paymentType === 'installment') {
            return $this->canCreateInstallment($customer, $totalAmount);
        }

        if ($paymentType === 'full') {
            return $this->canCreateFullBilling($customer, $totalAmount);
        }

        return true;
    }
}
