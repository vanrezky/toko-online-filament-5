<?php

namespace App\Filament\Resources\EmailTemplates\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\EmailTemplates\EmailTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmailTemplate extends EditRecord
{
    protected static string $resource = EmailTemplateResource::class;

    public function getTitle(): string
    {
        return __('admin/email-template-resource.pages.edit.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
