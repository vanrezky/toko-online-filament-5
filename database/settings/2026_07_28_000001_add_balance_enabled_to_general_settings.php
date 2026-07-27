<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.balance_enabled', true);
    }

    public function down(): void
    {
        $this->migrator->delete('general.balance_enabled');
    }
};
