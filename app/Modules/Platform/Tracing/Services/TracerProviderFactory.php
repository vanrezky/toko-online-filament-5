<?php

namespace App\Modules\Platform\Tracing\Services;

use App\Modules\Platform\Tracing\SpanProcessors\DbSpanProcessor;
use App\Modules\Platform\Tracing\Support\TracingAttributes;
use ArrayObject;
use OpenTelemetry\API\Common\Time\Clock;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\Protocols;
use OpenTelemetry\Contrib\Otlp\SpanExporter as OtlpSpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOffSampler;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\Sampler\TraceIdRatioBasedSampler;
use OpenTelemetry\SDK\Trace\SamplerInterface;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanExporterInterface;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
use Throwable;

/**
 * Builds the OpenTelemetry TracerProvider from the tracing configuration.
 *
 * The provider is only ever constructed after the config's enabled flag has
 * been checked by the service provider, so this class never runs while tracing
 * is disabled.
 */
final class TracerProviderFactory
{
    public function __construct(
        private readonly bool $testing = false,
        private readonly ?ArrayObject $memoryStorage = null,
    ) {}

    public function create(): TracerProviderInterface
    {
        $resource = $this->resource();

        $exporter = $this->exporter();

        $exportProcessor = $this->testing
            ? new SimpleSpanProcessor($exporter)
            : new BatchSpanProcessor($exporter, Clock::getDefault());

        $processors = [$exportProcessor];

        if ($this->persisting()) {
            $processors[] = new DbSpanProcessor;
        }

        return new TracerProvider($processors, $this->sampler(), $resource);
    }

    private function persisting(): bool
    {
        return (bool) config('tracing.persist', false);
    }

    private function resource(): ResourceInfo
    {
        $attributes = array_filter([
            TracingAttributes::SERVICE_NAME => (string) config('tracing.service_name'),
            TracingAttributes::SERVICE_VERSION => config('tracing.service_version'),
            TracingAttributes::DEPLOYMENT_ENVIRONMENT => (string) config('tracing.environment'),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $default = ResourceInfoFactory::defaultResource();

        if ($attributes === []) {
            return $default;
        }

        return $default->merge(ResourceInfo::create(Attributes::create($attributes)));
    }

    private function sampler(): SamplerInterface
    {
        $ratio = max(0.0, min(1.0, (float) config('tracing.sampler_ratio', 1.0)));

        return match (true) {
            $ratio >= 1.0 => new AlwaysOnSampler,
            $ratio <= 0.0 => new AlwaysOffSampler,
            default => new TraceIdRatioBasedSampler($ratio),
        };
    }

    private function exporter(): SpanExporterInterface
    {
        return match (config('tracing.exporter')) {
            'memory' => new InMemoryExporter($this->memoryStorage ?? new ArrayObject),
            default => $this->otlpExporter(),
        };
    }

    private function otlpExporter(): SpanExporterInterface
    {
        $endpoint = (string) config('tracing.exporter_endpoint');
        $protocol = (string) config('tracing.protocol');
        $contentType = Protocols::contentType($protocol);

        try {
            $transport = (new OtlpHttpTransportFactory)->create($endpoint, $contentType);

            return new OtlpSpanExporter($transport);
        } catch (Throwable $throwable) {
            report($throwable);

            // Degrade gracefully: without a usable exporter the request must
            // never break, so fall back to dropping spans in-process.
            return new InMemoryExporter;
        }
    }
}
