<x-filament-panels::page>
    {{ $this->form }}

    <x-filament::section :heading="__('admin/observability-executions-page.sections.executions')">
        @if ($executions === [])
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/observability-executions-page.value.no_executions') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/observability-executions-page.executions.correlation_id') }}</th>
                            <th class="pb-3 pr-4 font-medium">{{ __('admin/observability-executions-page.executions.last_event') }}</th>
                            <th class="pb-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($executions as $execution)
                            <tr class="border-t border-gray-200 dark:border-white/10">
                                <td class="py-3 pr-4 font-mono text-xs">{{ $execution['correlation_id'] }}</td>
                                <td class="whitespace-nowrap py-3 pr-4">
                                    @if ($execution['last_event_at'])
                                        {{ \Illuminate\Support\Carbon::parse($execution['last_event_at'])->format('d M Y, H:i:s') }}
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="py-3 text-right">
                                    <a href="{{ \App\Filament\Pages\ObservabilityExecutionDetail::getUrl(['correlation' => $execution['correlation_id']]) }}"
                                        class="text-primary-600 underline decoration-primary-400/50 underline-offset-2 transition-colors hover:text-primary-700 hover:decoration-primary-600 dark:text-primary-400 dark:hover:text-primary-300">{{ __('admin/observability-executions-page.executions.open') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>