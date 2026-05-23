<?php

namespace App\Filament\Resources\Vouchers\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Vouchers\VoucherResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVoucher extends EditRecord
{
    protected static string $resource = VoucherResource::class;

    public function getTitle(): string
    {
        return __('admin/voucher-resource.pages.edit.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')->label(__('admin/voucher-resource.fields.back'))->color('warning')->url($this->getResource()::getUrl('index')),
            DeleteAction::make(),
        ];
    }

}
