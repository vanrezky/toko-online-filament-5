<x-filament-panels::page>
    @if ($detail === null)
        <x-filament::section>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/observability-trace-detail-page.value.not_found') }}</p>
        </x-filament::section>
    @else
        <x-filament::section :heading="__('admin/observability-trace-detail-page.sections.summary')">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/observability-trace-detail-page.summary.operation') }}</dt>
                    <dd class="mt-1 break-words font-medium">{{ $detail->rootOperation }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/observability-trace-detail-page.summary.trace_id') }}</dt>
                    <dd class="mt-1 break-all font-mono text-xs">{{ $detail->traceId }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/observability-trace-detail-page.summary.correlation_id') }}</dt>
                    <dd class="mt-1 break-all font-mono text-xs">
                        @if ($detail->correlationId)
                            <span class="inline-flex items-center gap-2">
                                <span>{{ $detail->correlationId }}</span>
                                <button type="button" onclick="navigator.clipboard.writeText('{{ $detail->correlationId }}')"
                                    class="text-primary-600 underline decoration-primary-400/50 underline-offset-2 transition-colors hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                                    {{ __('admin/observability-trace-detail-page.actions.copy') }}
                                </button>
                            </span>
                        @else
                            <span class="text-gray-500 dark:text-gray-400">—</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/observability-trace-detail-page.summary.duration') }}</dt>
                    <dd class="mt-1 font-mono">
                        {{ $detail->durationMs !== null ? ($detail->durationMs >= 1000 ? number_format($detail->durationMs / 1000, 2).'s' : $detail->durationMs.'ms') : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/observability-trace-detail-page.summary.status') }}</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center gap-1.5">
                            @if ($detail->isPartial)
                                <span class="rounded-md bg-warning-50 px-2 py-1 font-medium text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">{{ __('admin/observability-traces-page.badge.partial') }}</span>
                            @endif
                            @if ($detail->status)
                                <span class="rounded-md px-2 py-1 font-medium
                                    {{ $detail->status === 'ERROR' ? 'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400' : ($detail->status === 'OK' ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-gray-50 text-gray-700 dark:bg-white/5 dark:text-gray-300') }}">
                                    {{ __('admin/observability-traces-page.status.'.$detail->status) }}
                                </span>
                            @else
                                <span class="text-gray-500 dark:text-gray-400">—</span>
                            @endif
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/observability-trace-detail-page.summary.spans') }}</dt>
                    <dd class="mt-1 text-lg font-semibold">{{ $detail->spanCount }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/observability-trace-detail-page.summary.errors') }}</dt>
                    <dd class="mt-1 text-lg font-semibold {{ $detail->errorCount > 0 ? 'text-danger-600 dark:text-danger-400' : '' }}">{{ $detail->errorCount }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/observability-trace-detail-page.summary.start') }}</dt>
                    <dd class="mt-1 whitespace-nowrap text-xs">{{ \Illuminate\Support\Carbon::createFromTimestamp($detail->startNs / 1000000000)->format('d M Y, H:i:s') }}</dd>
                </div>
            </dl>

            @if ($detail->correlationId && ($integration_logs_url || $audit_logs_url))
                <div class="mt-4 flex flex-wrap items-center gap-4 text-sm">
                    @if ($integration_logs_url)
                        <a href="{{ $integration_logs_url }}" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 text-primary-600 underline decoration-primary-400/50 underline-offset-2 transition-colors hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                            {{ __('admin/observability-trace-detail-page.actions.integration_logs') }}
                        </a>
                    @endif
                    @if ($audit_logs_url)
                        <a href="{{ $audit_logs_url }}" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 text-primary-600 underline decoration-primary-400/50 underline-offset-2 transition-colors hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                            {{ __('admin/observability-trace-detail-page.actions.audit_logs') }}
                        </a>
                    @endif
                </div>
            @endif
        </x-filament::section>

        <x-filament::section :heading="__('admin/observability-trace-detail-page.sections.waterfall')">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="w-1/3 pb-3 pr-4 font-medium">{{ __('admin/observability-trace-detail-page.waterfall.operation') }}</th>
                            <th class="pb-3 font-medium">{{ __('admin/observability-trace-detail-page.waterfall.timeline') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($detail->spans as $span)
                            <tr class="border-t border-gray-200 dark:border-white/10">
                                <td class="py-2 pr-4">
                                    <button type="button" x-data="{ open: false }" x-on:click="open = !open" class="flex items-center gap-2 text-left">
                                        <span class="inline-block h-3 w-3 rounded-full
                                            {{ $span->isFailed ? 'bg-danger-500' : ($span->isSlow ? 'bg-warning-500' : 'bg-primary-500') }}"></span>
                                        <span class="min-w-0 max-w-72 truncate font-medium">{{ $span->name }}</span>
                                        <span class="shrink-0 font-mono text-xs text-gray-500 dark:text-gray-400">
                                            {{ $span->durationMs !== null ? ($span->durationMs >= 1000 ? number_format($span->durationMs / 1000, 2).'s' : $span->durationMs.'ms') : '—' }}
                                        </span>
                                    </button>
                                </td>
                                <td class="py-2">
                                    <div class="relative h-6 w-full min-w-96 rounded-md bg-gray-100 dark:bg-white/5">
                                        <div
                                            class="absolute top-1 h-4 rounded-sm {{ $span->isFailed ? 'bg-danger-400 dark:bg-danger-500/70' : ($span->isSlow ? 'bg-warning-400 dark:bg-warning-500/70' : 'bg-primary-400 dark:bg-primary-500/70') }}"
                                            style="left: {{ number_format($span->leftPercent, 2) }}%; width: {{ number_format($span->widthPercent, 2) }}%;"
                                            title="{{ $span->name }}"></div>
                                    </div>
                                </td>
                            </tr>
                            <tr x-data="{ open: false }" x-show="open" x-cloak class="border-b border-gray-100 dark:border-white/5">
                                <td colspan="2" class="py-3">
                                    <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                        <div>
                                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin/observability-trace-detail-page.span.trace_id') }}</dt>
                                            <dd class="mt-0.5 break-all font-mono text-xs">{{ $span->traceId }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin/observability-trace-detail-page.span.span_id') }}</dt>
                                            <dd class="mt-0.5 break-all font-mono text-xs">{{ $span->spanId }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin/observability-trace-detail-page.span.parent_span_id') }}</dt>
                                            <dd class="mt-0.5 break-all font-mono text-xs">{{ $span->parentSpanId ?? '—' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin/observability-trace-detail-page.span.status') }}</dt>
                                            <dd class="mt-0.5">
                                                @if ($span->statusCode)
                                                    <span class="rounded-md px-2 py-0.5 text-xs font-medium
                                                        {{ $span->isFailed ? 'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400' : 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' }}">
                                                        {{ __('admin/observability-traces-page.status.'.$span->statusCode) }}
                                                    </span>
                                                @else
                                                    <span class="text-gray-500 dark:text-gray-400">—</span>
                                                @endif
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin/observability-trace-detail-page.span.start') }}</dt>
                                            <dd class="mt-0.5 text-xs">{{ \Illuminate\Support\Carbon::createFromTimestamp($span->startNs / 1000000000)->format('d M Y, H:i:s') }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin/observability-trace-detail-page.span.end') }}</dt>
                                            <dd class="mt-0.5 text-xs">
                                                {{ $span->endNs !== null ? \Illuminate\Support\Carbon::createFromTimestamp($span->endNs / 1000000000)->format('d M Y, H:i:s') : '—' }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin/observability-trace-detail-page.span.correlation_id') }}</dt>
                                            <dd class="mt-0.5 break-all font-mono text-xs">{{ $span->correlationId ?? '—' }}</dd>
                                        </div>
                                        @if ($span->integrationLogUrl)
                                            <div>
                                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin/observability-trace-detail-page.span.integration_log') }}</dt>
                                                <dd class="mt-0.5">
                                                    <a href="{{ $span->integrationLogUrl }}" target="_blank" rel="noopener noreferrer"
                                                        class="text-primary-600 underline decoration-primary-400/50 underline-offset-2 transition-colors hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                                                        {{ __('admin/observability-trace-detail-page.actions.view_record') }}
                                                    </a>
                                                </dd>
                                            </div>
                                        @endif
                                    </dl>

                                    @if ($span->statusDescription)
                                        <div class="mt-3">
                                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin/observability-trace-detail-page.span.status_description') }}</dt>
                                            <dd class="mt-0.5 break-words text-xs text-danger-600 dark:text-danger-400">{{ $span->statusDescription }}</dd>
                                        </div>
                                    @endif

                                    @if ($span->attributes !== [])
                                        <div class="mt-3">
                                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin/observability-trace-detail-page.span.attributes') }}</dt>
                                            <dd class="mt-1 overflow-x-auto rounded-md bg-gray-50 p-2 dark:bg-white/5">
                                                <table class="w-full text-xs">
                                                    @foreach ($span->attributes as $key => $value)
                                                        <tr>
                                                            <td class="pr-3 font-mono text-gray-500 dark:text-gray-400">{{ $key }}</td>
                                                            <td class="break-all font-mono">{{ is_scalar($value) || $value === null ? (string) $value : json_encode($value) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </table>
                                            </dd>
                                        </div>
                                    @endif

                                    @if ($span->events !== [])
                                        <div class="mt-3">
                                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin/observability-trace-detail-page.span.events') }}</dt>
                                            <dd class="mt-1 overflow-x-auto rounded-md bg-gray-50 p-2 dark:bg-white/5">
                                                <table class="w-full text-xs">
                                                    @foreach ($span->events as $event)
                                                        <tr>
                                                            <td class="pr-3 whitespace-nowrap font-mono">{{ $event['name'] }}</td>
                                                            <td class="pr-3 whitespace-nowrap text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Carbon::createFromTimestamp($event['epoch_ns'] / 1000000000)->format('d M Y, H:i:s') }}</td>
                                                            <td class="break-all font-mono">{{ $event['attributes'] === [] ? '—' : json_encode($event['attributes']) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </table>
                                            </dd>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section :heading="__('admin/observability-trace-detail-page.sections.tree')">
            <div class="overflow-x-auto">
                <ul class="space-y-1 text-sm">
                    @foreach ($detail->tree as $node)
                        @include('filament.pages.partials.trace-span-tree', ['node' => $node, 'depth' => 0])
                    @endforeach
                </ul>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>