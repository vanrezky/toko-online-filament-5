<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\SchoolUnit;
use App\Models\Transaction;
use App\Modules\Platform\Health\Checks\ApplicationHealthCheck;
use App\Modules\Platform\Integration\Support\IntegrationCorrelationContext;
use App\Modules\Platform\Support\Correlation;
use App\Observers\CustomerObserver;
use App\Observers\SchoolUnitObserver;
use App\Observers\TransactionObserver;
use App\Overrides\Superconductor\LaravelVibes\Mcp\Capabilities\Prompts\ReadLogPrompt;
use App\Support\MediaLibrary\UploadPathGenerator;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Http\Client\PendingRequest;
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
        $this->app->scoped(IntegrationCorrelationContext::class);
    }

    public function boot(): void
    {
        config(['media-library.path_generator' => UploadPathGenerator::class]);

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

        $this->assignConsoleCorrelationIds();
        $this->registerHttpCorrelationMacro();
    }

    /** @var array<int, string> Long-running queue worker commands that must not anchor a console correlation ID. */
    private const QUEUE_WORKER_COMMANDS = [
        'queue:work',
        'queue:listen',
        'horizon',
        'horizon:supervisor',
        'horizon:work',
        'horizon:listen',
    ];

    private function assignConsoleCorrelationIds(): void
    {
        $this->app['events']->listen(CommandStarting::class, static function (CommandStarting $event): void {
            if (in_array($event->command, self::QUEUE_WORKER_COMMANDS, true)) {
                return;
            }

            Correlation::id();
        });
    }

    private function registerHttpCorrelationMacro(): void
    {
        PendingRequest::macro('withCorrelation', function (): PendingRequest {
            /** @var PendingRequest $this */
            return $this->withHeaders(Correlation::headers());
        });
    }
}
