<?php

namespace Tests\Feature\Platform\Audit;

use App\Modules\Platform\Audit\Services\AuditLogService;
use App\Modules\Platform\Support\Correlation;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ReflectionProperty;
use Tests\TestCase;

class CorrelationAuditMetadataTest extends TestCase
{
    public function test_request_metadata_includes_the_active_correlation_id(): void
    {
        $correlationId = (string) Str::uuid();
        Correlation::set($correlationId);
        $this->forceRunningInConsole(false);
        $this->app->instance('request', Request::create('/test'));

        $metadata = app(AuditLogService::class)->requestMetadata();

        $this->assertSame($correlationId, $metadata['correlation_id'] ?? null);
        $this->assertArrayHasKey('ip', $metadata);
    }

    public function test_request_metadata_is_empty_in_console_context_without_correlation(): void
    {
        $this->forceRunningInConsole(true);

        $metadata = app(AuditLogService::class)->requestMetadata();

        $this->assertSame([], $metadata);
    }

    public function test_request_metadata_keeps_the_correlation_id_in_console_context(): void
    {
        $correlationId = (string) Str::uuid();
        Correlation::set($correlationId);
        $this->forceRunningInConsole(true);

        $metadata = app(AuditLogService::class)->requestMetadata();

        $this->assertSame(['correlation_id' => $correlationId], $metadata);
    }

    private function forceRunningInConsole(bool $value): void
    {
        $reflection = new ReflectionProperty(Application::class, 'isRunningInConsole');
        $reflection->setValue($this->app, $value);
    }
}
