<div>
    <x-filament-panels::form>
        {{ $this->form }}
    </x-filament-panels::form>

    @if(!empty($previewData))
        <div class="mt-6">
            <x-filament::section>
                <x-slot name="heading">
                    Ringkasan
                </x-slot>

                <div class="grid grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Bulan</p>
                        <p class="text-lg font-semibold">{{ $previewData['month_name'] }} {{ $previewData['year'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Anggota</p>
                        <p class="text-lg font-semibold">{{ $previewData['total_customers'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Potongan</p>
                        <p class="text-lg font-semibold">Rp {{ number_format($previewData['total_deduction'], 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Cicilan Aktif</p>
                        <p class="text-lg font-semibold">{{ $previewData['total_installments'] }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section class="mt-4">
                <x-slot name="heading">
                    Detail Potongan per Anggota
                </x-slot>

                <x-filament::table>
                    <thead>
                        <tr>
                            <x-filament::tables::header-cell>ID</x-filament::tables::header-cell>
                            <x-filament::tables::header-cell>Nama</x-filament::tables::header-cell>
                            <x-filament::tables::header-cell>Level</x-filament::tables::header-cell>
                            <x-filament::tables::header-cell>Total Potongan</x-filament::tables::header-cell>
                            <x-filament::tables::header-cell>Cicilan Aktif</x-filament::tables::header-cell>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($previewData['details'] as $detail)
                            <tr>
                                <x-filament::tables::cell>{{ $detail['customer']->id }}</x-filament::tables::cell>
                                <x-filament::tables::cell>{{ $detail['customer']->full_name }}</x-filament::tables::cell>
                                <x-filament::tables::cell>{{ $detail['level'] }}</x-filament::tables::cell>
                                <x-filament::tables::cell>Rp {{ number_format($detail['total_deduction'], 0, ',', '.') }}</x-filament::tables::cell>
                                <x-filament::tables::cell>{{ $detail['active_installments'] }}</x-filament::tables::cell>
                            </tr>
                        @endforeach
                    </tbody>
                </x-filament::table>
            </x-filament::section>
        </div>
    @endif
</div>