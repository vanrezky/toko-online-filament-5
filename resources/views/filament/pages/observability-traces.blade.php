<x-filament-panels::page>
    {{ $this->form }}

    <x-filament::section :heading="__('admin/observability-traces-page.sections.traces')">
        @if ($traces->isEmpty())
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/observability-traces-page.value.no_traces') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/observability-traces-page.traces.timestamp') }}</th>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/observability-traces-page.traces.trace_id') }}</th>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/observability-traces-page.traces.correlation_id') }}</th>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/observability-traces-page.traces.operation') }}</th>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/observability-traces-page.traces.status') }}</th>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/observability-traces-page.traces.duration') }}</th>
                            <th class="pb-3 pr-4 text-right font-medium">{{ __('admin/observability-traces-page.traces.spans') }}</th>
                            <th class="pb-3 pr-4 text-right font-medium">{{ __('admin/observability-traces-page.traces.errors') }}</th>
                            <th class="pb-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($traces as $trace)
                            <tr class="border-t border-gray-200 dark:border-white/10">
                                <td class="whitespace-nowrap py-3 pr-4">
                                    {{ \Illuminate\Support\Carbon::createFromTimestamp($trace->startNs / 1000000000)->format('d M Y, H:i:s') }}
                                </td>
                                <td class="py-3 pr-4 font-mono text-xs">{{ $trace->traceId }}</td>
                                <td class="py-3 pr-4 font-mono text-xs">
                                    @if ($trace->correlationId)
                                        {{ $trace->correlationId }}
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="max-w-56 truncate py-3 pr-4">{{ $trace->rootOperation }}</td>
                                <td class="py-3 pr-4">
                                    <span class="inline-flex items-center gap-1.5">
                                        @if ($trace->isPartial)
                                            <span class="rounded-md bg-warning-50 px-2 py-1 font-medium text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">{{ __('admin/observability-traces-page.badge.partial') }}</span>
                                        @endif
                                        @if ($trace->status)
                                            <span class="rounded-md px-2 py-1 font-medium
                                                {{ $trace->status === 'ERROR' ? 'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400' : ($trace->status === 'OK' ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-gray-50 text-gray-700 dark:bg-white/5 dark:text-gray-300') }}">
                                                {{ __('admin/observability-traces-page.status.'.$trace->status) }}
                                            </span>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400">—</span>
                                        @endif
                                    </span>
                                </td>
                                <td class="whitespace-nowrap py-3 pr-4 font-mono">
                                    {{ $trace->durationMs !== null ? ($trace->durationMs >= 1000 ? number_format($trace->durationMs / 1000, 2).'s' : $trace->durationMs.'ms') : '—' }}
                                </td>
                                <td class="whitespace-nowrap py-3 pr-4 text-right">{{ $trace->spanCount }}</td>
                                <td class="whitespace-nowrap py-3 pr-4 text-right">
                                    @if ($trace->errorCount > 0)
                                        <span class="text-danger-600 dark:text-danger-400">{{ $trace->errorCount }}</span>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">0</span>
                                    @endif
                                </td>
                                <td class="py-3 text-right">
                                    <a href="{{ \App\Filament\Pages\ObservabilityTraceDetail::getUrl(['trace' => $trace->traceId]) }}"
                                        class="text-primary-600 underline decoration-primary-400/50 underline-offset-2 transition-colors hover:text-primary-700 hover:decoration-primary-600 dark:text-primary-400 dark:hover:text-primary-300">{{ __('admin/observability-traces-page.traces.open') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">
                    {{ __('admin/observability-traces-page.pagination.total', ['total' => number_format($traces->total())]) }}
                </span>
                <div class="flex items-center gap-3">
                    @if ($page > 1)
                        <button type="button" wire:click="goToPage({{ $page - 1 }})"
                            class="text-primary-600 underline decoration-primary-400/50 underline-offset-2 transition-colors hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                            &larr; {{ __('admin/observability-traces-page.pagination.prev') }}
                        </button>
                    @endif
                    @if ($traces->hasMorePages())
                        <button type="button" wire:click="goToPage({{ $page + 1 }})"
                            class="text-primary-600 underline decoration-primary-400/50 underline-offset-2 transition-colors hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                            {{ __('admin/observability-traces-page.pagination.next') }} &rarr;
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>