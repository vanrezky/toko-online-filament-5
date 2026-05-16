<?php

namespace App\Filament\Resources\VoucherResource\Pages;

use App\Filament\Resources\VoucherResource;
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
            Actions\Action::make('back')->label(__('admin/voucher-resource.fields.back'))->color('warning')->url($this->getResource()::getUrl('index')),
            Actions\DeleteAction::make(),
        ];
    }

}
