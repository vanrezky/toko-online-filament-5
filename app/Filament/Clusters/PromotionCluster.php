<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class PromotionCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-gift';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'promotions';

    public static function getNavigationLabel(): string
    {
        return __('admin/cluster-promotion.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/cluster-promotion.navigation_group');
    }
}
