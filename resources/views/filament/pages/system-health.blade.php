<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">{{ __('admin/system-health-page.overall.heading') }}</x-slot>

        <p @class([
            'text-2xl font-semibold',
            'text-success-600 dark:text-success-400' => $snapshot->overall === 'healthy',
            'text-warning-600 dark:text-warning-400' => $snapshot->overall === 'warning',
            'text-danger-600 dark:text-danger-400' => $snapshot->overall === 'failed',
            'text-gray-600 dark:text-gray-400' => $snapshot->overall === 'unknown',
        ])>{{ $snapshot->overallLabel() }}</p>
    </x-filament::section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($snapshot->checks as $check)
            <x-filament::section :heading="__('admin/system-health-page.checks.'.$check['name'])">
                <p @class([
                    'font-semibold',
                    'text-success-600 dark:text-success-400' => $check['status'] === 'healthy',
                    'text-warning-600 dark:text-warning-400' => $check['status'] === 'warning',
                    'text-danger-600 dark:text-danger-400' => $check['status'] === 'failed',
                    'text-gray-600 dark:text-gray-400' => $check['status'] === 'unknown',
                ])>{{ __('admin/system-health-page.status.'.$check['status']) }}</p>

                @if ($check['message'])
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $check['message'] }}</p>
                @endif

                @if ($check['meta'] !== [])
                    <dl class="mt-3 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        @foreach ($check['meta'] as $key => $value)
                            <div class="flex justify-between gap-3">
                                <dt>{{ __('admin/system-health-page.meta.'.$key) }}</dt>
                                <dd class="font-medium text-gray-950 dark:text-white">{{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}{{ $key === 'disk_space_used_percentage' ? '%' : '' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
