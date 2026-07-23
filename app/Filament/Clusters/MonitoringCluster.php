<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class MonitoringCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'monitoring';

    public static function getNavigationLabel(): string
    {
        return __('admin/cluster-monitoring.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return null;
    }
}
