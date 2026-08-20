<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class ObservabilityCluster extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'observability';

    public static function getNavigationLabel(): string
    {
        return __('admin/observability-overview-page.cluster.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Platform';
    }
}
