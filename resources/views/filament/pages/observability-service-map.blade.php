<x-filament-panels::page>
    {{ $this->form }}

    <x-filament::section :heading="__('admin/observability-service-map-page.sections.map')">
        @if ($snapshot->nodes === [])
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/observability-service-map-page.value.no_data') }}</p>
        @else
            <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                {{ __('admin/observability-service-map-page.value.generated_at', ['time' => \Illuminate\Support\Carbon::createFromTimestamp($snapshot->fromNs / 1000000000)->format('d M Y, H:i:s')]) }}
            </p>

            <div class="flex flex-wrap items-center gap-3">
                @foreach ($snapshot->nodes as $node)
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-2.5 w-2.5 rounded-full {{ $node->errorRate > 0 ? 'bg-danger-500' : 'bg-success-500' }}"></span>
                            <span class="max-w-56 truncate font-medium">{{ $node->operation }}</span>
                        </div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin/observability-service-map-page.metrics.requests', ['count' => number_format($node->requests)]) }}
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($snapshot->edges !== [])
                <div class="mt-4">
                    <h3 class="mb-2 text-sm font-medium">{{ __('admin/observability-service-map-page.edges.title') }}</h3>
                    <ul class="space-y-1 text-sm">
                        @foreach ($snapshot->edges as $edge)
                            <li class="flex items-center gap-2">
                                <span class="max-w-48 truncate font-mono text-xs">{{ $edge->from }}</span>
                                <span class="text-gray-400">→</span>
                                <span class="max-w-48 truncate font-mono text-xs">{{ $edge->to }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">({{ $edge->calls }})</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif
    </x-filament::section>

    <x-filament::section :heading="__('admin/observability-service-map-page.sections.metrics')">
        @if ($snapshot->nodes === [])
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/observability-service-map-page.value.no_metrics') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/observability-service-map-page.metrics.operation') }}</th>
                            <th class="pb-3 pr-4 text-right font-medium">{{ __('admin/observability-service-map-page.metrics.requests') }}</th>
                            <th class="pb-3 pr-4 text-right font-medium">{{ __('admin/observability-service-map-page.metrics.error_rate') }}</th>
                            <th class="pb-3 pr-4 text-right font-medium">{{ __('admin/observability-service-map-page.metrics.avg') }}</th>
                            <th class="pb-3 text-right font-medium">{{ __('admin/observability-service-map-page.metrics.p95') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($snapshot->nodes as $node)
                            <tr class="border-t border-gray-200 dark:border-white/10">
                                <td class="max-w-56 truncate py-3 pr-4 font-medium">{{ $node->operation }}</td>
                                <td class="whitespace-nowrap py-3 pr-4 text-right font-mono">{{ number_format($node->requests) }}</td>
                                <td class="whitespace-nowrap py-3 pr-4 text-right font-mono">
                                    @if ($node->errorRate !== null)
                                        <span class="{{ $node->errorRate > 0 ? 'text-danger-600 dark:text-danger-400' : '' }}">{{ number_format($node->errorRate * 100, 2) }}%</span>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap py-3 pr-4 text-right font-mono">
                                    @if ($node->avgMs !== null)
                                        {{ $node->avgMs >= 1000 ? number_format($node->avgMs / 1000, 2).'s' : number_format($node->avgMs, 1).'ms' }}
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap py-3 text-right font-mono">
                                    @if ($node->p95Ms !== null)
                                        {{ $node->p95Ms >= 1000 ? number_format($node->p95Ms / 1000, 2).'s' : number_format($node->p95Ms, 1).'ms' }}
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>