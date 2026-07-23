<?php

namespace App\Filament\Resources\Sliders\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Sliders\SliderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSliders extends ListRecords
{
    protected static string $resource = SliderResource::class;

    public function getTitle(): string
    {
        return __('admin/slider-resource.pages.list.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
