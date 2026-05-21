<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.installment_min_order_amount', 1000000);
    }

    public function down(): void
    {
        $this->migrator->delete('general.installment_min_order_amount');
    }
};
