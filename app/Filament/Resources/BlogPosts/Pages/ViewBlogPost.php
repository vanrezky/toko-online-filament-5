<?php

namespace App\Filament\Resources\BlogPosts\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\BlogPosts\BlogPostResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBlogPost extends ViewRecord
{
    protected static string $resource = BlogPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
