<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void { $this->migrator->add('general.transaction_time_limit_minutes', 1440); }
    public function down(): void { $this->migrator->delete('general.transaction_time_limit_minutes'); }
};
