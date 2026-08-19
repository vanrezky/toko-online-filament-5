<?php

namespace App\Filament\Resources\IntegrationLogs\Pages;

use App\Filament\Resources\IntegrationLogs\IntegrationLogResource;
use Filament\Resources\Pages\ListRecords;

class ListIntegrationLogs extends ListRecords
{
    protected static string $resource = IntegrationLogResource::class;

    public function getTitle(): string
    {
        return __('admin/integration-log-resource.pages.index.title');
    }
}
