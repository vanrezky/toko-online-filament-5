<x-filament-panels::page>
    {{-- Filter Form --}}
    {{ $this->form }}

    @if (!empty($previewData))
        {{-- Summary Section --}}
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                <div class="flex items-center justify-between">
                    <span>{{ __('admin/payroll-export-page.sections.summary') }}</span>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/20 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-500/30">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-primary-500"></span>
                        Preview
                    </span>
                </div>
            </x-slot>

            <x-slot name="description">
                {{ __('admin/payroll-export-page.summary_labels.month') }}: {{ $previewData['month_name'] }}
                {{ $previewData['year'] }}
            </x-slot>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                {{-- Period Card --}}
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('admin/payroll-export-page.summary_labels.month') }}
                    </p>
                    <p class="mt-2 text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                        {{ $previewData['month_name'] }} {{ $previewData['year'] }}
                    </p>
                </div>

                {{-- Total Customers Card --}}
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('admin/payroll-export-page.summary_labels.total_customers') }}
                    </p>
                    <p class="mt-2 text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                        {{ $previewData['total_customers'] }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">anggota</p>
                </div>

                {{-- Total Deduction Card --}}
                <div
                    class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/5">
                    <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                        {{ __('admin/payroll-export-page.summary_labels.total_deduction') }}
                    </p>
                    <p class="mt-2 text-xl font-bold tracking-tight text-emerald-700 dark:text-emerald-300">
                        Rp {{ number_format($previewData['total_deduction'], 0, ',', '.') }}
                    </p>
                </div>

                {{-- Bills Breakdown Card --}}
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Tagihan
                    </p>
                    <div class="mt-2 flex items-baseline gap-3">
                        <div>
                            <span
                                class="text-xl font-bold tracking-tight text-blue-600 dark:text-blue-400">{{ $previewData['total_full_bills'] ?? 0 }}</span>
                            <span class="ml-1 text-xs text-gray-400 dark:text-gray-500">full</span>
                        </div>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <div>
                            <span
                                class="text-xl font-bold tracking-tight text-amber-600 dark:text-amber-400">{{ $previewData['total_installments'] }}</span>
                            <span class="ml-1 text-xs text-gray-400 dark:text-gray-500">cicilan</span>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- Detail Table --}}
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                {{ __('admin/payroll-export-page.sections.details') }}
            </x-slot>

            <x-slot name="description">
                {{ count($previewData['details'] ?? []) }} anggota ditemukan
            </x-slot>

            <div class="-mx-6 -mb-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-t border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5">
                            <th
                                class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('admin/payroll-export-page.table_headers.id') }}
                            </th>
                            <th
                                class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('admin/payroll-export-page.table_headers.name') }}
                            </th>
                            <th
                                class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('admin/payroll-export-page.table_headers.level') }}
                            </th>
                            <th
                                class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('admin/payroll-export-page.table_headers.total_deduction') }}
                            </th>
                            <th
                                class="whitespace-nowrap px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('admin/payroll-export-page.table_headers.active_installments') }}
                            </th>
                            <th
                                class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('admin/payroll-export-page.table_headers.references') }}
                            </th>
                            <th
                                class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Ringkasan Jenis
                            </th>
                            <th
                                class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('admin/payroll-export-page.table_headers.bill_items') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse($previewData['details'] as $index => $detail)
                            <tr
                                class="{{ $index % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50/50 dark:bg-white/[0.015]' }} transition-colors hover:bg-primary-50/50 dark:hover:bg-primary-500/5">
                                {{-- ID --}}
                                <td
                                    class="whitespace-nowrap px-4 py-3 text-sm tabular-nums text-gray-400 dark:text-gray-500">
                                    #{{ $detail['customer_id'] }}
                                </td>

                                {{-- Name --}}
                                <td
                                    class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ $detail['customer_name'] }}
                                </td>

                                {{-- Level --}}
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span
                                        class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700">
                                        {{ $detail['level'] }}
                                    </span>
                                </td>

                                {{-- Total Deduction --}}
                                <td
                                    class="whitespace-nowrap px-4 py-3 text-right text-sm font-bold tabular-nums text-gray-950 dark:text-white">
                                    Rp {{ number_format($detail['total_deduction'], 0, ',', '.') }}
                                </td>

                                {{-- Active Installments --}}
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    @if ($detail['active_installments'] > 0)
                                        <span
                                            class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-amber-100 px-2 text-xs font-bold tabular-nums text-amber-700 dark:bg-amber-500/15 dark:text-amber-400">
                                            {{ $detail['active_installments'] }}
                                        </span>
                                    @else
                                        <span class="text-sm text-gray-300 dark:text-gray-600">&mdash;</span>
                                    @endif
                                </td>

                                {{-- References --}}
                                <td class="max-w-[200px] px-4 py-3 text-xs">
                                    <span
                                        class="block break-words text-gray-600 dark:text-gray-400">{{ $detail['references'] ?: '-' }}</span>
                                </td>

                                {{-- Type Summary --}}
                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-1.5">
                                        <div
                                            class="inline-flex items-center gap-2 rounded-md bg-blue-50 px-2.5 py-1 ring-1 ring-inset ring-blue-600/10 dark:bg-blue-500/10 dark:ring-blue-500/20">
                                            <span
                                                class="text-xs font-semibold text-blue-700 dark:text-blue-400">Full</span>
                                            <span
                                                class="text-[11px] text-blue-500/70 dark:text-blue-400/60">&times;{{ $detail['full_bill_count'] }}</span>
                                            <span
                                                class="ml-auto text-xs font-medium tabular-nums text-blue-700 dark:text-blue-300">
                                                Rp {{ number_format($detail['full_bill_total'], 0, ',', '.') }}
                                            </span>
                                        </div>
                                        <div
                                            class="inline-flex items-center gap-2 rounded-md bg-amber-50 px-2.5 py-1 ring-1 ring-inset ring-amber-600/10 dark:bg-amber-500/10 dark:ring-amber-500/20">
                                            <span
                                                class="text-xs font-semibold text-amber-700 dark:text-amber-400">Cicilan</span>
                                            <span
                                                class="text-[11px] text-amber-500/70 dark:text-amber-400/60">&times;{{ $detail['installment_bill_count'] }}</span>
                                            <span
                                                class="ml-auto text-xs font-medium tabular-nums text-amber-700 dark:text-amber-300">
                                                Rp {{ number_format($detail['installment_bill_total'], 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                {{-- Bill Items --}}
                                <td class="px-4 py-3 text-xs" x-data="{ expanded: false }">
                                    @php
                                        $payments = collect($detail['payments'] ?? []);
                                        $previewItems = $payments->take(2);
                                        $hasMore = $payments->count() > 2;
                                    @endphp

                                    {{-- Collapsed View --}}
                                    <div class="space-y-1.5" x-show="!expanded">
                                        @foreach ($previewItems as $item)
                                            @php $isFull = $item['type'] === 'full'; @endphp
                                            <div class="flex items-center gap-2 leading-relaxed">
                                                <span
                                                    class="inline-flex shrink-0 items-center rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $isFull ? 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400' }}">
                                                    {{ $isFull ? 'Full' : 'Cicilan' }}
                                                </span>
                                                <span class="min-w-0 truncate">
                                                    @if (!empty($item['transaction_uuid']))
                                                        <a href="{{ \App\Filament\Resources\Transactions\TransactionResource::getUrl('view', ['record' => $item['transaction_uuid']]) }}"
                                                            target="_blank" rel="noopener noreferrer"
                                                            class="font-semibold text-blue-600 underline decoration-blue-400/50 underline-offset-2 transition-colors hover:text-blue-700 hover:decoration-blue-600 dark:text-blue-400 dark:decoration-blue-500/30 dark:hover:text-blue-300">{{ $item['reference'] ?? '-' }}</a>
                                                    @else
                                                        <span
                                                            class="font-medium text-gray-700 dark:text-gray-300">{{ $item['reference'] ?? '-' }}</span>
                                                    @endif
                                                </span>
                                                <span class="shrink-0 tabular-nums text-gray-500 dark:text-gray-400">
                                                    Rp {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}
                                                </span>
                                            </div>
                                        @endforeach
                                        @if ($hasMore)
                                            <button type="button"
                                                class="mt-0.5 text-[11px] font-semibold text-blue-600 transition-colors hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                                @click="expanded = true">
                                                &#9662; Lihat {{ $payments->count() - 2 }} lainnya
                                            </button>
                                        @endif
                                    </div>

                                    {{-- Expanded View --}}
                                    <div class="max-h-44 space-y-1.5 overflow-y-auto pr-1" x-show="expanded" x-cloak>
                                        @foreach ($payments as $item)
                                            @php $isFull = $item['type'] === 'full'; @endphp
                                            <div class="flex items-center gap-2 leading-relaxed">
                                                <span
                                                    class="inline-flex shrink-0 items-center rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $isFull ? 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400' }}">
                                                    {{ $isFull ? 'Full' : 'Cicilan' }}
                                                </span>
                                                <span class="min-w-0 truncate">
                                                    @if (!empty($item['transaction_uuid']))
                                                        <a href="{{ \App\Filament\Resources\Transactions\TransactionResource::getUrl('view', ['record' => $item['transaction_uuid']]) }}"
                                                            target="_blank" rel="noopener noreferrer"
                                                            class="font-semibold text-blue-600 underline decoration-blue-400/50 underline-offset-2 transition-colors hover:text-blue-700 hover:decoration-blue-600 dark:text-blue-400 dark:decoration-blue-500/30 dark:hover:text-blue-300">{{ $item['reference'] ?? '-' }}</a>
                                                    @else
                                                        <span
                                                            class="font-medium text-gray-700 dark:text-gray-300">{{ $item['reference'] ?? '-' }}</span>
                                                    @endif
                                                </span>
                                                <span class="shrink-0 tabular-nums text-gray-500 dark:text-gray-400">
                                                    Rp {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}
                                                </span>
                                            </div>
                                        @endforeach
                                        @if ($hasMore)
                                            <button type="button"
                                                class="mt-0.5 text-[11px] font-semibold text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                                @click="expanded = false">
                                                &#9652; Ringkas
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Tidak ada tagihan untuk periode yang dipilih.
                                    </p>
                                </td>
                            </tr>
                        @endforelse

                    </tbody>
                    {{-- <tfoot>
                        @if (!empty($previewData['details']))
                            <tr class="border-t-2 border-gray-300 bg-gray-50 dark:border-white/10 dark:bg-white/5">
                                <td colspan="3"
                                    class="px-4 py-3 text-right text-xl font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                                    Total
                                </td>
                                <td
                                    class="px-4 py-3 text-right text-xl font-bold tabular-nums text-gray-950 dark:text-white">
                                    Rp {{ number_format($previewData['total_deduction'], 0, ',', '.') }}
                                </td>
                                <td
                                    class="px-4 py-3 text-center text-sm font-bold tabular-nums text-gray-950 dark:text-white">
                                    {{ $previewData['total_installments'] }}
                                </td>
                                <td colspan="3"></td>
                            </tr>
                        @endif
                    </tfoot> --}}
                </table>
            </div>

            {{-- Footer Info --}}
            <div
                class="-mx-6 -mb-6 mt-5 flex items-center justify-between border-gray-200 bg-gray-50/50 px-6 py-3 dark:border-white/10 dark:bg-white/[0.02]">
                <p class="text-xs text-gray-400 dark:text-gray-500">
                    Menampilkan {{ count($previewData['details'] ?? []) }} anggota &middot; Periode
                    {{ $previewData['month_name'] }} {{ $previewData['year'] }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500">
                    Digenerate {{ now()->translatedFormat('d M Y, H:i') }}
                </p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
