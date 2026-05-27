<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="mt-2 flex items-center justify-start">
            <x-filament::button
                type="submit"
                size="lg"
                icon="heroicon-m-check"
            >
                {{ __('admin/page-manage-courier.view.save_settings') }}
            </x-filament::button>
        </div>
    </form>

    <x-filament::section>
        <x-slot name="heading">
            {{ __('admin/page-manage-courier.view.available_couriers') }}
        </x-slot>
        <x-slot name="description">
            {{ __('admin/page-manage-courier.view.toggle_status_description') }}
        </x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;">
            @foreach ($this->getCouriers() as $courier)
                <div
                    @if ($courier->is_active)
                        style="border:1px solid rgb(34 197 94 / 0.5);background-color:rgb(34 197 94 / 0.05);border-radius:12px;padding:16px;"
                    @else
                        style="border:1px solid rgb(239 68 68 / 0.5);background-color:rgb(239 68 68 / 0.05);border-radius:12px;padding:16px;"
                    @endif
                >
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="height:56px;width:56px;flex-shrink:0;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:8px;border:1px solid #e5e7eb;background:#fff;padding:4px;">
                            @if ($courier->logo)
                                <img src="{{ $courier->logo_url }}" alt="{{ $courier->name }}" style="display:block;height:100%;width:100%;object-fit:contain;">
                            @else
                                <x-heroicon-o-truck style="height:28px;width:28px;color:#9ca3af;" />
                            @endif
                        </div>

                        <div style="min-width:0;flex:1;">
                            <h3 style="margin:0;font-size:16px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;{{ $courier->is_active ? 'color: rgb(22 163 74);' : 'color: rgb(220 38 38);' }}">
                                {{ $courier->name }}
                            </h3>
                            <p style="margin:2px 0 0 0;font-size:13px;color:#6b7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                {{ $courier->fullname }}
                            </p>
                        </div>

                        <button
                            type="button"
                            wire:click="toggleStatus({{ $courier->id }})"
                            wire:click.prevent="toggleStatus({{ $courier->id }})"
                            style="position:relative;display:inline-flex;height:24px;width:44px;flex-shrink:0;cursor:pointer;border:0;border-radius:999px;padding:2px;transition:background-color .2s ease;{{ $courier->is_active ? 'background-color: rgb(34 197 94);' : 'background-color: rgb(156 163 175);' }}"
                        >
                            <span style="pointer-events:none;display:inline-block;height:20px;width:20px;border-radius:999px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:transform .2s ease;{{ $courier->is_active ? 'transform: translateX(20px);' : 'transform: translateX(0);' }}"></span>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
