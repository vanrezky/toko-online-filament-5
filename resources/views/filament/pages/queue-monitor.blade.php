<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-filament::section>
            <x-slot name="heading">{{ __('admin/queue-monitor-page.cards.horizon') }}</x-slot>

            <p @class([
                'text-2xl font-semibold',
                'text-success-600 dark:text-success-400' => $snapshot->hasWorkers(),
                'text-warning-600 dark:text-warning-400' => ! $snapshot->hasWorkers(),
            ])>
                {{ $snapshot->statusLabel() }}
            </p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('admin/queue-monitor-page.cards.pending') }}</x-slot>

            <p class="text-2xl font-semibold">{{ number_format($snapshot->pendingJobs) }}</p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('admin/queue-monitor-page.cards.failed') }}</x-slot>

            <p class="text-2xl font-semibold">{{ number_format($snapshot->failedJobs) }}</p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('admin/queue-monitor-page.cards.processed') }}</x-slot>

            <p class="text-2xl font-semibold">
                {{ $snapshot->processedJobs === null ? __('admin/queue-monitor-page.value.unavailable') : number_format($snapshot->processedJobs) }}
            </p>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">{{ __('admin/queue-monitor-page.cards.queues') }}</x-slot>

        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ $snapshot->queues === [] ? __('admin/queue-monitor-page.value.no_queues') : implode(', ', $snapshot->queues) }}
        </p>
    </x-filament::section>

    <x-filament::section :heading="__('admin/queue-monitor-page.cards.workload')">
        @if ($snapshot->workload === [])
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/queue-monitor-page.value.no_workload') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/queue-monitor-page.workload.queue') }}</th>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/queue-monitor-page.workload.pending') }}</th>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/queue-monitor-page.workload.wait') }}</th>
                            <th class="pb-3 font-medium">{{ __('admin/queue-monitor-page.workload.processes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($snapshot->workload as $queue)
                            <tr class="border-t border-gray-200 dark:border-white/10">
                                <td class="py-3 pr-4 font-medium">{{ $queue['name'] }}</td>
                                <td class="py-3 pr-4">{{ number_format($queue['length']) }}</td>
                                <td class="py-3 pr-4">{{ trans_choice('admin/queue-monitor-page.workload.seconds', $queue['wait'], ['count' => $queue['wait']]) }}</td>
                                <td class="py-3">{{ number_format($queue['processes']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
