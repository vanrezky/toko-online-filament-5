<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\SchoolUnit;
use App\Models\Transaction;
use App\Modules\Platform\Health\Checks\ApplicationHealthCheck;
use App\Observers\CustomerObserver;
use App\Observers\SchoolUnitObserver;
use App\Observers\TransactionObserver;
use App\Overrides\Superconductor\LaravelVibes\Mcp\Capabilities\Prompts\ReadLogPrompt;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;
use Superconductor\Capabilities\Prompts\Support\Facades\MCP;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Health::checks([
            ApplicationHealthCheck::new()->name('application')->label('Application'),
            DatabaseCheck::new()
                ->connectionName(env('HEALTH_DB_CONNECTION', config('database.default')))
                ->name('database')
                ->label('Database'),
            RedisCheck::new()
                ->connectionName(env('HEALTH_REDIS_CONNECTION', 'default'))
                ->name('redis')
                ->label('Redis'),
            QueueCheck::new()
                ->onQueue(env('HEALTH_QUEUE_NAME', config('queue.connections.redis.queue', 'default')))
                ->failWhenHealthJobTakesLongerThanMinutes((int) env('HEALTH_QUEUE_FAILURE_MINUTES', 5))
                ->name('queue')
                ->label('Queue'),
            UsedDiskSpaceCheck::new()
                ->filesystemName(env('HEALTH_DISK_PATH', base_path()))
                ->warnWhenUsedSpaceIsAbovePercentage((int) env('HEALTH_DISK_WARNING_PERCENTAGE', 70))
                ->failWhenUsedSpaceIsAbovePercentage((int) env('HEALTH_DISK_FAILURE_PERCENTAGE', 90))
                ->name('disk')
                ->label('Disk'),
        ]);

        JsonResource::withoutWrapping();

        Transaction::observe(TransactionObserver::class);
        SchoolUnit::observe(SchoolUnitObserver::class);
        Customer::observe(CustomerObserver::class);

        if ($this->app->bound('mcp-prompts')) {
            $registrar = $this->app->make('mcp-prompts');
            if (isset($registrar->capabilities['read-logs'])) {
                unset($registrar->capabilities['read-logs']);
            }
            MCP::prompt('read-logs', ReadLogPrompt::class);
        }
    }
}
