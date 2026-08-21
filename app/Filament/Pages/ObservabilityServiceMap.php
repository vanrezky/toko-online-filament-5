<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\ObservabilityCluster;
use App\Modules\Platform\Tracing\Services\ServiceMapService;
use App\Modules\Platform\Tracing\ValueObjects\ServiceMapSnapshot;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ObservabilityServiceMap extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $slug = 'service-map';

    protected static ?string $cluster = ObservabilityCluster::class;

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.observability-service-map';

    public string $range = '24h';

    public function getTitle(): string
    {
        return __('admin/observability-service-map-page.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/observability-service-map-page.navigation_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(1)
                ->schema([
                    Select::make('range')
                        ->label(__('admin/observability-service-map-page.range.label'))
                        ->options([
                            '1h' => __('admin/observability-service-map-page.range.1h'),
                            '24h' => __('admin/observability-service-map-page.range.24h'),
                            '7d' => __('admin/observability-service-map-page.range.7d'),
                        ])
                        ->default('24h')
                        ->live(),
                ]),
        ]);
    }

    /** @return array{snapshot: ServiceMapSnapshot} */
    public function getViewData(): array
    {
        return [
            'snapshot' => app(ServiceMapService::class)->map($this->range),
        ];
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()?->user();

        return (bool) $user?->is_super_user || (bool) $user?->can('View:ServiceMap');
    }
}
