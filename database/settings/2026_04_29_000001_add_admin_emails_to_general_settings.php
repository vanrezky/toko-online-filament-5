<?php

use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.admin_emails', 'admin@example.com');
    }

    public function down(): void
    {
        $this->migrator->delete('general.admin_emails');
    }
};
