<?php

namespace App\Providers;

use App\Models\Transaction;
use App\Models\SchoolUnit;
use App\Models\Customer;
use App\Observers\CustomerObserver;
use App\Observers\SchoolUnitObserver;
use App\Observers\TransactionObserver;
use App\Overrides\Superconductor\LaravelVibes\Mcp\Capabilities\Prompts\ReadLogPrompt;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use Superconductor\Capabilities\Prompts\Support\Facades\MCP;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
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
