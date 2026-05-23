<?php

namespace App\Filament\Resources\Vouchers\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Vouchers\VoucherResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVouchers extends ListRecords
{
    protected static string $resource = VoucherResource::class;

    public function getTitle(): string
    {
        return __('admin/voucher-resource.pages.list.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
