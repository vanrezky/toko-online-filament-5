<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="mt-2 flex items-center justify-start">
            <x-filament::button type="submit" size="lg" icon="heroicon-m-check">
                {{ __('admin/page-manage-courier.view.save_settings') }}
            </x-filament::button>
        </div>
    </form>

    <x-filament::section compact>
        <x-slot name="heading">
            {{ __('admin/page-manage-courier.view.available_couriers') }}
        </x-slot>

        <x-slot name="description">
            {{ __('admin/page-manage-courier.view.toggle_status_description') }}
        </x-slot>

        <div class="grid grid-cols-[repeat(auto-fill,minmax(220px,1fr))] gap-2">
            @foreach ($this->getCouriers() as $courier)
                <div
                    @class([
                        'flex min-w-0 items-center gap-2.5 rounded-lg border px-3 py-2.5 transition-colors',
                        'border-success-200 bg-success-50/50 dark:border-success-400/30 dark:bg-success-400/10' => $courier->is_active,
                        'border-gray-200 bg-gray-50/50 dark:border-white/10 dark:bg-white/5' => ! $courier->is_active,
                    ])
                >
                    <div class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-md border border-gray-200 bg-white p-1.5 dark:border-white/10 dark:bg-gray-900">
                        @if ($courier->logo)
                            <img src="{{ $courier->logo_url }}" alt="{{ $courier->name }}" class="size-full object-contain">
                        @else
                            <x-heroicon-o-truck class="size-5 text-gray-400" />
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <h3
                            @class([
                                'truncate text-sm font-semibold',
                                'text-success-700 dark:text-success-300' => $courier->is_active,
                                'text-gray-700 dark:text-gray-200' => ! $courier->is_active,
                            ])
                        >
                            {{ $courier->name }}
                        </h3>
                        <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">
                            {{ $courier->fullname }}
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="toggleStatus({{ $courier->id }})"
                        wire:loading.attr="disabled"
                        aria-pressed="{{ $courier->is_active ? 'true' : 'false' }}"
                        aria-label="{{ $courier->is_active ? __('admin/page-manage-courier.view.deactivate_courier', ['courier' => $courier->name]) : __('admin/page-manage-courier.view.activate_courier', ['courier' => $courier->name]) }}"
                        @class([
                            'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full p-0.5 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 disabled:cursor-wait disabled:opacity-60',
                            'bg-success-500' => $courier->is_active,
                            'bg-gray-300 dark:bg-gray-600' => ! $courier->is_active,
                        ])
                    >
                        <span
                            @class([
                                'pointer-events-none inline-block size-4 rounded-full bg-white shadow-sm transition-transform duration-200',
                                'translate-x-4' => $courier->is_active,
                            ])
                        ></span>
                    </button>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
