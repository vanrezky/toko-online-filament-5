<x-filament-panels::page>
    <x-filament::section :heading="__('admin/cache-management-page.overview.heading')">
        <dl class="space-y-2 text-sm">
            @foreach ($snapshot->metadata as $key => $value)
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-600 dark:text-gray-400">{{ __('admin/cache-management-page.metadata.'.$key) }}</dt>
                    <dd class="font-medium text-gray-950 dark:text-white">{{ $value }}</dd>
                </div>
            @endforeach
            <div class="flex justify-between gap-4">
                <dt class="text-gray-600 dark:text-gray-400">{{ __('admin/cache-management-page.metadata.status') }}</dt>
                <dd @class([
                    'font-semibold',
                    'text-success-600 dark:text-success-400' => $snapshot->status === 'connected',
                    'text-danger-600 dark:text-danger-400' => $snapshot->status === 'unavailable',
                ])>{{ $snapshot->statusLabel() }}</dd>
            </div>
        </dl>

        @if ($snapshot->message)
            <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">{{ $snapshot->message }}</p>
        @endif
    </x-filament::section>

    <x-filament::section
        :heading="__('admin/cache-management-page.managed.heading')"
        :description="__('admin/cache-management-page.managed.description')"
    >
        <x-filament::actions
            :actions="$this->getGroupCacheActions()"
            class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4"
        />
    </x-filament::section>
</x-filament-panels::page>
