<x-filament-panels::page>
    @if ($timeline->correlationId)
        <x-filament::section :heading="__('admin/observability-execution-detail-page.sections.summary')">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/observability-execution-detail-page.summary.correlation_id') }}</dt>
                    <dd class="mt-1 break-all font-mono text-xs">{{ $timeline->correlationId }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/observability-execution-detail-page.summary.integration_events') }}</dt>
                    <dd class="mt-1 text-lg font-semibold">{{ $timeline->integrationCount }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/observability-execution-detail-page.summary.audit_events') }}</dt>
                    <dd class="mt-1 text-lg font-semibold">{{ $timeline->auditCount }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/observability-execution-detail-page.summary.queue_events') }}</dt>
                    <dd class="mt-1 text-lg font-semibold">{{ $timeline->queueCount }}</dd>
                </div>
            </dl>
        </x-filament::section>
    @endif

    <x-filament::section :heading="__('admin/observability-execution-detail-page.sections.timeline')">
        @if ($timeline->isEmpty())
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/observability-execution-detail-page.value.no_events') }}</p>
        @else
            <ol class="relative space-y-6 border-l border-gray-200 pl-6 dark:border-white/10">
                @foreach ($timeline->events as $event)
                    <li class="relative">
                        <span class="absolute -left-[27px] top-1.5 h-3 w-3 rounded-full ring-4 ring-white dark:ring-gray-900
                            {{ $event['status'] === 'failed' ? 'bg-danger-500' : 'bg-primary-500' }}"></span>
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-medium">{{ $event['title'] }}</p>
                                @if ($event['description'])
                                    <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">{{ $event['description'] }}</p>
                                @endif
                            </div>
                            <div class="flex shrink-0 items-center gap-3 text-xs">
                                @if ($event['occurred_at'])
                                    <span class="whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        {{ \Illuminate\Support\Carbon::createFromTimestamp($event['occurred_at'])->format('d M Y, H:i:s') }}
                                    </span>
                                @endif
                                @if ($event['duration_ms'] !== null)
                                    <span class="whitespace-nowrap font-mono">
                                        {{ $event['duration_ms'] >= 1000 ? number_format($event['duration_ms'] / 1000, 2).'s' : $event['duration_ms'].'ms' }}
                                    </span>
                                @endif
                                @if ($event['status'])
                                    <span class="inline-flex items-center rounded-md px-2 py-1 font-medium
                                        {{ $event['status'] === 'success' ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : ($event['status'] === 'failed' ? 'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400' : 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400') }}">
                                        {{ __('admin/observability-executions-page.status.'.$event['status']) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="mt-1 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                            <span class="inline-flex items-center gap-1">
                                <x-filament::icon icon="heroicon-o-server-stack" class="h-3.5 w-3.5" />
                                {{ __('admin/observability-execution-detail-page.sources.'.$event['source']) }}
                            </span>
                            @if ($event['link'])
                                <a href="{{ $event['link'] }}" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 text-primary-600 underline decoration-primary-400/50 underline-offset-2 transition-colors hover:text-primary-700 hover:decoration-primary-600 dark:text-primary-400 dark:hover:text-primary-300">
                                    {{ __('admin/observability-execution-detail-page.actions.view_record') }}
                                </a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </x-filament::section>
</x-filament-panels::page>