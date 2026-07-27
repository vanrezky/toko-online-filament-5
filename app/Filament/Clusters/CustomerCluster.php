<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class CustomerCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'customer-management';

    public static function getNavigationLabel(): string
    {
        return __('admin/cluster-customer.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/cluster-customer.navigation_group');
    }
}
