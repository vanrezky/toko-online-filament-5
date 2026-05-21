<x-filament-panels::page>
    {{ $this->form }}

    @if(!empty($previewData))
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                {{ __('admin/payroll-export-page.sections.summary') }}
            </x-slot>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-5">
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
                <div>
                    <p class="text-sm text-gray-500">{{ __('admin/payroll-export-page.summary_labels.total_full_bills') }}</p>
                    <p class="text-lg font-semibold">{{ $previewData['total_full_bills'] ?? 0 }}</p>
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
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('admin/payroll-export-page.table_headers.references') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ringkasan Jenis</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('admin/payroll-export-page.table_headers.bill_items') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($previewData['details'] as $detail)
                            <tr class="border-t">
                                <td class="px-4 py-2 text-sm">{{ $detail['customer_id'] }}</td>
                                <td class="px-4 py-2 text-sm">{{ $detail['customer_name'] }}</td>
                                <td class="px-4 py-2 text-sm">{{ $detail['level'] }}</td>
                                <td class="px-4 py-2 text-sm text-right">Rp {{ number_format($detail['total_deduction'], 0, ',', '.') }}</td>
                                <td class="px-4 py-2 text-sm text-center">{{ $detail['active_installments'] }}</td>
                                <td class="px-4 py-2 text-xs break-words">{{ $detail['references'] ?: '-' }}</td>
                                <td class="px-4 py-2 text-xs">
                                    <div class="space-y-1">
                                        <div class="inline-flex items-center gap-2 rounded bg-blue-50 px-2 py-1 text-blue-700">
                                            <span class="font-medium">Full</span>
                                            <span>{{ $detail['full_bill_count'] }}</span>
                                            <span>-</span>
                                            <span>Rp {{ number_format($detail['full_bill_total'], 0, ',', '.') }}</span>
                                        </div>
                                        <div class="inline-flex items-center gap-2 rounded bg-amber-50 px-2 py-1 text-amber-700">
                                            <span class="font-medium">Cicilan</span>
                                            <span>{{ $detail['installment_bill_count'] }}</span>
                                            <span>-</span>
                                            <span>Rp {{ number_format($detail['installment_bill_total'], 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-xs" x-data="{ expanded: false }">
                                    @php
                                        $payments = collect($detail['payments'] ?? []);
                                        $previewItems = $payments->take(2);
                                        $hasMore = $payments->count() > 2;
                                    @endphp

                                    <div class="space-y-1 pr-1" x-show="!expanded">
                                        @foreach($previewItems as $item)
                                            @php
                                                $badgeClass = $item['type'] === 'full'
                                                    ? 'bg-blue-100 text-blue-700'
                                                    : 'bg-amber-100 text-amber-700';
                                            @endphp
                                            <div class="leading-relaxed">
                                                <span class="inline-flex items-center rounded px-2 py-0.5 font-medium {{ $badgeClass }}">
                                                    {{ $item['type'] === 'full' ? 'Full' : 'Cicilan' }}
                                                </span>
                                                <span class="ml-1">
                                                    @if(!empty($item['transaction_uuid']))
                                                        <a
                                                            href="{{ \App\Filament\Resources\TransactionResource::getUrl('view', ['record' => $item['transaction_uuid']]) }}"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            class="font-medium text-primary-600 underline decoration-primary-400 underline-offset-2 transition-colors hover:text-primary-700 hover:decoration-primary-700"
                                                        >
                                                            {{ $item['reference'] ?? '-' }}
                                                        </a>
                                                    @else
                                                        {{ $item['reference'] ?? '-' }}
                                                    @endif
                                                    - Rp {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}
                                                </span>
                                            </div>
                                        @endforeach
                                        @if($hasMore)
                                            <button
                                                type="button"
                                                class="mt-1 text-xs font-medium text-primary-600 hover:text-primary-700"
                                                @click="expanded = true"
                                            >
                                                Lihat {{ $payments->count() - 2 }} item lainnya
                                            </button>
                                        @endif
                                    </div>

                                    <div class="max-h-36 overflow-y-auto space-y-1 pr-1" x-show="expanded" x-cloak>
                                        @foreach($payments as $item)
                                            @php
                                                $badgeClass = $item['type'] === 'full'
                                                    ? 'bg-blue-100 text-blue-700'
                                                    : 'bg-amber-100 text-amber-700';
                                            @endphp
                                            <div class="leading-relaxed">
                                                <span class="inline-flex items-center rounded px-2 py-0.5 font-medium {{ $badgeClass }}">
                                                    {{ $item['type'] === 'full' ? 'Full' : 'Cicilan' }}
                                                </span>
                                                <span class="ml-1">
                                                    @if(!empty($item['transaction_uuid']))
                                                        <a
                                                            href="{{ \App\Filament\Resources\TransactionResource::getUrl('view', ['record' => $item['transaction_uuid']]) }}"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            class="font-medium text-primary-600 underline decoration-primary-400 underline-offset-2 transition-colors hover:text-primary-700 hover:decoration-primary-700"
                                                        >
                                                            {{ $item['reference'] ?? '-' }}
                                                        </a>
                                                    @else
                                                        {{ $item['reference'] ?? '-' }}
                                                    @endif
                                                    - Rp {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}
                                                </span>
                                            </div>
                                        @endforeach
                                        @if($hasMore)
                                            <button
                                                type="button"
                                                class="mt-1 text-xs font-medium text-gray-600 hover:text-gray-700"
                                                @click="expanded = false"
                                            >
                                                Ringkas
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="border-t">
                                <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500">
                                    Tidak ada tagihan untuk periode yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
