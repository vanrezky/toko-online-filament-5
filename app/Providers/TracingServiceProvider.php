<?php

namespace App\Providers;

use App\Modules\Platform\Tracing\Http\TracingHttpMiddleware;
use App\Modules\Platform\Tracing\Listeners\DatabaseQueryListener;
use App\Modules\Platform\Tracing\Queue\TracingJobMiddleware;
use App\Modules\Platform\Tracing\Services\TraceContext;
use App\Modules\Platform\Tracing\Services\TraceManager;
use App\Modules\Platform\Tracing\Services\TracerProviderFactory;
use ArrayObject;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class TracingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TraceManager::class, function (Application $app): TraceManager {
            if (! (bool) config('tracing.enabled', false)) {
                return new TraceManager(null);
            }

            $testing = $app->environment('testing');

            $storage = null;
            $provider = null;

            if ((string) config('tracing.exporter') === 'memory') {
                $storage = $app->bound(ArrayObject::class)
                    ? $app->make(ArrayObject::class)
                    : new ArrayObject;

                $provider = (new TracerProviderFactory($testing, $storage))->create();
            } else {
                $provider = (new TracerProviderFactory($testing))->create();
            }

            return new TraceManager($provider, $storage);
        });

        $this->app->scoped(TraceContext::class, function (Application $app): TraceContext {
            return new TraceContext($app->make(TraceManager::class));
        });
    }

    public function boot(): void
    {
        $this->app['events']->listen(
            QueryExecuted::class,
            DatabaseQueryListener::class,
        );

        Http::globalMiddleware(TracingHttpMiddleware::make());

        $this->app['events']->listen(
            JobProcessing::class,
            static fn (JobProcessing $event) => TracingJobMiddleware::handleStart($event),
        );

        $this->app['events']->listen(
            JobProcessed::class,
            static fn (JobProcessed $event) => TracingJobMiddleware::handleEnd($event),
        );

        $this->app['events']->listen(
            JobFailed::class,
            static fn (JobFailed $event) => TracingJobMiddleware::handleEnd($event),
        );

        $this->app['events']->listen(
            JobExceptionOccurred::class,
            static fn (JobExceptionOccurred $event) => TracingJobMiddleware::handleEnd($event),
        );
    }
}
