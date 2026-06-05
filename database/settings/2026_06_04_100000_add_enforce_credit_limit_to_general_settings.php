<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.enforce_credit_limit', true);
    }

    public function down(): void
    {
        $this->migrator->delete('general.enforce_credit_limit');
    }
};
