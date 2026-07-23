<?php

namespace App\Filament\Resources\Sliders\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Sliders\SliderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSlider extends EditRecord
{
    protected static string $resource = SliderResource::class;

    public function getTitle(): string
    {
        return __('admin/slider-resource.pages.edit.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
