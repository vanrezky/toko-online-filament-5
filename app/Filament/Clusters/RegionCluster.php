<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class RegionCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-map';

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'regions';

    public static function getNavigationLabel(): string
    {
        return __('admin/cluster-region.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/cluster-region.navigation_group');
    }
}
