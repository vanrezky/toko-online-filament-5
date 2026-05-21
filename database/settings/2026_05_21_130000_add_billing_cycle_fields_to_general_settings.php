<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.billing_cutoff_day', 25);
        $this->migrator->add('general.billing_due_day', 5);
        $this->migrator->add('general.billing_due_month_offset', 1);
    }

    public function down(): void
    {
        $this->migrator->delete('general.billing_cutoff_day');
        $this->migrator->delete('general.billing_due_day');
        $this->migrator->delete('general.billing_due_month_offset');
    }
};
