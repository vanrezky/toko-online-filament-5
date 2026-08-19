<?php

namespace App\Console\Commands;

use App\Modules\Platform\Integration\Models\IntegrationLog;
use Illuminate\Console\Command;

class PruneIntegrationLogs extends Command
{
    protected $signature = 'integration-logs:prune';

    protected $description = 'Delete integration logs past the configured retention period.';

    public function handle(): int
    {
        $retentionDays = max(1, (int) config('integration-logging.retention_days', 90));
        $deleted = IntegrationLog::query()
            ->where('created_at', '<', now()->subDays($retentionDays))
            ->delete();

        $this->info("Pruned {$deleted} integration logs.");

        return self::SUCCESS;
    }
}
