<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->rename('general.force_sll', 'general.force_ssl');
        $this->migrator->encrypt('general.mail_password');
    }

    public function down(): void
    {
        $this->migrator->decrypt('general.mail_password');
        $this->migrator->rename('general.force_ssl', 'general.force_sll');
    }
};
