<x-filament-panels::page>
    {{ $this->form }}

    <div class="mt-2 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-filament::section>
            <x-slot name="heading">{{ __('admin/observability-overview-page.cards.total_calls') }}</x-slot>

            <p class="text-2xl font-semibold">{{ number_format($snapshot->totalCalls) }}</p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('admin/observability-overview-page.cards.failed_calls') }}</x-slot>

            <p @class([
                'text-2xl font-semibold',
                'text-danger-600 dark:text-danger-400' => $snapshot->failedCalls > 0,
            ])>{{ number_format($snapshot->failedCalls) }}</p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('admin/observability-overview-page.cards.avg_duration') }}</x-slot>

            <p class="text-2xl font-semibold">
                {{ $snapshot->avgDurationMs === null ? __('admin/observability-overview-page.value.unavailable') : __('admin/observability-overview-page.value.duration', ['ms' => number_format($snapshot->avgDurationMs)]) }}
            </p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('admin/observability-overview-page.cards.slowest') }}</x-slot>

            <p class="text-2xl font-semibold">
                {{ $snapshot->slowestDurationMs === null ? __('admin/observability-overview-page.value.unavailable') : __('admin/observability-overview-page.value.duration', ['ms' => number_format($snapshot->slowestDurationMs)]) }}
            </p>
        </x-filament::section>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-filament::section>
            <x-slot name="heading">{{ __('admin/observability-overview-page.cards.queue_failed') }}</x-slot>

            <p @class([
                'text-2xl font-semibold',
                'text-danger-600 dark:text-danger-400' => $snapshot->queue->failedJobs > 0,
            ])>{{ number_format($snapshot->queue->failedJobs) }}</p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('admin/observability-overview-page.cards.queue_processed') }}</x-slot>

            <p class="text-2xl font-semibold">
                {{ $snapshot->queue->processedJobs === null ? __('admin/observability-overview-page.value.unavailable') : number_format($snapshot->queue->processedJobs) }}
            </p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('admin/observability-overview-page.cards.health') }}</x-slot>

            <p @class([
                'text-2xl font-semibold',
                'text-success-600 dark:text-success-400' => $snapshot->health->overall === 'healthy',
                'text-warning-600 dark:text-warning-400' => $snapshot->health->overall === 'warning',
                'text-danger-600 dark:text-danger-400' => $snapshot->health->overall === 'failed',
                'text-gray-600 dark:text-gray-400' => $snapshot->health->overall === 'unknown',
            ])>{{ $snapshot->health->overallLabel() }}</p>
        </x-filament::section>
    </div>

    <x-filament::section :heading="__('admin/observability-overview-page.sections.provider_performance')">
        @if ($snapshot->providerPerformance === [])
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/observability-overview-page.value.no_data') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/observability-overview-page.provider.provider') }}</th>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/observability-overview-page.provider.calls') }}</th>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/observability-overview-page.provider.failed') }}</th>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/observability-overview-page.provider.failed_rate') }}</th>
                            <th class="pb-3 font-medium">{{ __('admin/observability-overview-page.provider.avg_duration') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($snapshot->providerPerformance as $row)
                            <tr class="border-t border-gray-200 dark:border-white/10">
                                <td class="py-3 pr-4 font-medium">{{ $row['provider'] }}</td>
                                <td class="py-3 pr-4">{{ number_format($row['calls']) }}</td>
                                <td class="py-3 pr-4">
                                    @if ($row['failed'] > 0)
                                        <span class="text-danger-600 dark:text-danger-400">{{ number_format($row['failed']) }}</span>
                                    @else
                                        <span>0</span>
                                    @endif
                                </td>
                                <td class="py-3 pr-4">{{ number_format($row['failed_rate'], 1) }}%</td>
                                <td class="py-3">{{ $row['avg_duration_ms'] === null ? __('admin/observability-overview-page.value.unavailable') : __('admin/observability-overview-page.value.duration', ['ms' => number_format($row['avg_duration_ms'])]) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    <x-filament::section :heading="__('admin/observability-overview-page.sections.slow_calls')">
        @if ($snapshot->slowCalls === [])
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/observability-overview-page.value.no_slow_calls') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/observability-overview-page.slow_calls.provider') }}</th>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/observability-overview-page.slow_calls.endpoint') }}</th>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/observability-overview-page.slow_calls.duration') }}</th>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/observability-overview-page.slow_calls.status') }}</th>
                            <th class="pb-3 font-medium">{{ __('admin/observability-overview-page.slow_calls.correlation_id') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($snapshot->slowCalls as $row)
                            <tr class="border-t border-gray-200 dark:border-white/10">
                                <td class="py-3 pr-4 font-medium">{{ $row['provider'] }}</td>
                                <td class="max-w-xs truncate py-3 pr-4 font-mono text-xs">{{ $row['endpoint'] ?? '—' }}</td>
                                <td class="py-3 pr-4">{{ __('admin/observability-overview-page.value.duration', ['ms' => number_format($row['duration_ms'])]) }}</td>
                                <td class="py-3 pr-4">
                                    <span @class([
                                        'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium',
                                        'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' => $row['status'] === 'success',
                                        'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400' => $row['status'] === 'failed',
                                        'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400' => $row['status'] === 'pending',
                                    ])>{{ __('admin/observability-overview-page.status.'.$row['status']) }}</span>
                                </td>
                                <td class="py-3 font-mono text-xs">
                                    <a href="{{ \App\Filament\Resources\IntegrationLogs\IntegrationLogResource::getUrl('index') }}?filters[search][value]={{ urlencode($row['correlation_id']) }}"
                                        target="_blank" rel="noopener noreferrer"
                                        class="text-primary-600 underline decoration-primary-400/50 underline-offset-2 transition-colors hover:text-primary-700 hover:decoration-primary-600 dark:text-primary-400 dark:hover:text-primary-300">{{ $row['correlation_id'] }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    <x-filament::section :heading="__('admin/observability-overview-page.sections.recent_errors')">
        @if ($snapshot->recentErrors === [])
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/observability-overview-page.value.no_errors') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/observability-overview-page.recent_errors.time') }}</th>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/observability-overview-page.recent_errors.provider') }}</th>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/observability-overview-page.recent_errors.endpoint') }}</th>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/observability-overview-page.recent_errors.error') }}</th>
                            <th class="pb-3 font-medium">{{ __('admin/observability-overview-page.recent_errors.correlation_id') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($snapshot->recentErrors as $row)
                            <tr class="border-t border-gray-200 dark:border-white/10">
                                <td class="whitespace-nowrap py-3 pr-4">{{ \Illuminate\Support\Carbon::parse($row['created_at'])->format('d M Y, H:i') }}</td>
                                <td class="py-3 pr-4 font-medium">{{ $row['provider'] }}</td>
                                <td class="max-w-xs truncate py-3 pr-4 font-mono text-xs">{{ $row['endpoint'] ?? '—' }}</td>
                                <td class="max-w-md py-3 pr-4">
                                    @if ($row['error_class'])
                                        <span class="font-mono text-xs text-danger-600 dark:text-danger-400">{{ $row['error_class'] }}</span>
                                    @endif
                                    @if ($row['error_message'])
                                        <span class="block text-xs text-gray-600 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($row['error_message'], 140) }}</span>
                                    @endif
                                </td>
                                <td class="py-3 font-mono text-xs">
                                    <a href="{{ \App\Filament\Resources\IntegrationLogs\IntegrationLogResource::getUrl('index') }}?filters[search][value]={{ urlencode($row['correlation_id']) }}"
                                        target="_blank" rel="noopener noreferrer"
                                        class="text-primary-600 underline decoration-primary-400/50 underline-offset-2 transition-colors hover:text-primary-700 hover:decoration-primary-600 dark:text-primary-400 dark:hover:text-primary-300">{{ $row['correlation_id'] }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>