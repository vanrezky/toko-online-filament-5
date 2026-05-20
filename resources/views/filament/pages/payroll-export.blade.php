<x-filament-panels::page>
    {{ $this->form }}

    @if(!empty($previewData))
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                {{ __('admin/payroll-export-page.sections.summary') }}
            </x-slot>

            <div class="grid grid-cols-4 gap-4">
                <div>
                    <p class="text-sm text-gray-500">{{ __('admin/payroll-export-page.summary_labels.month') }}</p>
                    <p class="text-lg font-semibold">{{ $previewData['month_name'] }} {{ $previewData['year'] }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('admin/payroll-export-page.summary_labels.total_customers') }}</p>
                    <p class="text-lg font-semibold">{{ $previewData['total_customers'] }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('admin/payroll-export-page.summary_labels.total_deduction') }}</p>
                    <p class="text-lg font-semibold">Rp {{ number_format($previewData['total_deduction'], 0, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('admin/payroll-export-page.summary_labels.total_installments') }}</p>
                    <p class="text-lg font-semibold">{{ $previewData['total_installments'] }}</p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section class="mt-6">
            <x-slot name="heading">
                {{ __('admin/payroll-export-page.sections.details') }}
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('admin/payroll-export-page.table_headers.id') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('admin/payroll-export-page.table_headers.name') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('admin/payroll-export-page.table_headers.level') }}</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('admin/payroll-export-page.table_headers.total_deduction') }}</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">{{ __('admin/payroll-export-page.table_headers.active_installments') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($previewData['details'] as $detail)
                            <tr class="border-t">
                                <td class="px-4 py-2 text-sm">{{ $detail['customer']->id }}</td>
                                <td class="px-4 py-2 text-sm">{{ $detail['customer']->full_name }}</td>
                                <td class="px-4 py-2 text-sm">{{ $detail['level'] }}</td>
                                <td class="px-4 py-2 text-sm text-right">Rp {{ number_format($detail['total_deduction'], 0, ',', '.') }}</td>
                                <td class="px-4 py-2 text-sm text-center">{{ $detail['active_installments'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>