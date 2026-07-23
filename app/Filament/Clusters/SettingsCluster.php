<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class SettingsCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'settings';

    public static function getNavigationLabel(): string
    {
        return __('admin/cluster-settings.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/cluster-settings.navigation_group');
    }
}
