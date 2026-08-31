<x-filament-panels::page>
    {{ $this->form }}

    @if ($this->hasPreview())
        <x-filament::section class="mt-6" :heading="__('admin/product-import.sections.preview')">
            @if (!empty($preview['token']))
                <p class="mb-4 text-sm text-warning-600 dark:text-warning-400">
                    {{ __('admin/product-import.preview_expires', ['expires_at' => \Carbon\Carbon::parse($preview['expires_at'])->translatedFormat('d M Y H:i')]) }}
                </p>
            @endif

            @if (!empty($preview['errors']))
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="border-b text-left"><th class="px-3 py-2">{{ __('admin/product-import.table.row') }}</th><th class="px-3 py-2">{{ __('admin/product-import.table.column') }}</th><th class="px-3 py-2">{{ __('admin/product-import.table.error') }}</th></tr></thead>
                        <tbody>
                            @foreach ($preview['errors'] as $error)
                                <tr class="border-b"><td class="px-3 py-2">{{ $error['row'] ?: '-' }}</td><td class="px-3 py-2">{{ $error['column'] }}</td><td class="px-3 py-2">{{ $error['message'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">{{ __('admin/product-import.preview_count', ['count' => count($preview['rows'] ?? [])]) }}</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="border-b text-left"><th class="px-3 py-2">#</th><th class="px-3 py-2">{{ __('admin/product-import.fields.name') }}</th><th class="px-3 py-2">{{ __('admin/product-import.fields.code') }}</th><th class="px-3 py-2">{{ __('admin/product-import.fields.category') }}</th><th class="px-3 py-2">{{ __('admin/product-import.fields.product_type') }}</th><th class="px-3 py-2">{{ __('admin/product-import.fields.price') }}</th><th class="px-3 py-2">{{ __('admin/product-import.fields.sale_price') }}</th><th class="px-3 py-2">{{ __('admin/product-import.fields.stock') }}</th><th class="px-3 py-2">{{ __('admin/product-import.fields.weight') }}</th><th class="px-3 py-2">{{ __('admin/product-import.fields.warehouse') }}</th><th class="px-3 py-2">{{ __('admin/product-import.fields.image_url') }}</th></tr></thead>
                        <tbody>
                            @foreach ($preview['rows'] ?? [] as $index => $row)
                                <tr class="border-b"><td class="px-3 py-2">{{ $index + 1 }}</td><td class="px-3 py-2">{{ $row['name'] }}</td><td class="px-3 py-2">{{ $row['code'] }}</td><td class="px-3 py-2">{{ $row['category_name'] }}</td><td class="px-3 py-2">{{ $row['digital'] === \App\Constants\Status::DIGITAL_PRODUCT ? __('admin/product-import.types.digital') : __('admin/product-import.types.physical') }}</td><td class="px-3 py-2">Rp {{ number_format($row['price'], 0, ',', '.') }}</td><td class="px-3 py-2">Rp {{ number_format($row['sale_price'], 0, ',', '.') }}</td><td class="px-3 py-2">{{ $row['stock'] }}</td><td class="px-3 py-2">{{ $row['weight'] }}</td><td class="px-3 py-2">{{ $row['warehouse_name'] }}</td><td class="px-3 py-2">{{ $row['image_url'] ?: '-' }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
