<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class ContentCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'content';

    public static function getNavigationLabel(): string
    {
        return __('admin/cluster-content.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return null;
    }
}
