<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\ObservabilityCluster;
use App\Modules\Platform\Observability\Services\ObservabilityService;
use App\Modules\Platform\Observability\ValueObjects\ObservabilitySnapshot;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\CarbonImmutable;

class ObservabilityOverview extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $slug = 'overview';

    protected static ?string $cluster = ObservabilityCluster::class;

    protected string $view = 'filament.pages.observability-overview';

    public string $range = '24h';

    public ?string $customFrom = null;

    public ?string $customUntil = null;

    public function getTitle(): string
    {
        return __('admin/observability-overview-page.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/observability-overview-page.navigation_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)
                ->schema([
                    Select::make('range')
                        ->label(__('admin/observability-overview-page.filters.range'))
                        ->options([
                            '24h' => __('admin/observability-overview-page.ranges.24h'),
                            '7d' => __('admin/observability-overview-page.ranges.7d'),
                            '30d' => __('admin/observability-overview-page.ranges.30d'),
                            'custom' => __('admin/observability-overview-page.ranges.custom'),
                        ])
                        ->live()
                        ->afterStateUpdated(function (string $state): void {
                            if ($state !== 'custom') {
                                $this->customFrom = null;
                                $this->customUntil = null;
                            }
                        })
                        ->columnSpan(1),
                    DatePicker::make('customFrom')
                        ->label(__('admin/observability-overview-page.filters.from'))
                        ->visible(fn (): bool => $this->range === 'custom')
                        ->columnSpan(1),
                    DatePicker::make('customUntil')
                        ->label(__('admin/observability-overview-page.filters.until'))
                        ->visible(fn (): bool => $this->range === 'custom')
                        ->columnSpan(1),
                ]),
        ]);
    }

    /** @return array{snapshot: ObservabilitySnapshot, range: string} */
    public function getViewData(): array
    {
        $service = app(ObservabilityService::class);
        [$from, $until] = $this->rangeBoundary();

        return [
            'snapshot' => $service->snapshot($from, $until),
            'range' => $this->range,
        ];
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function rangeBoundary(): array
    {
        $now = now()->toImmutable();

        return match ($this->range) {
            '7d' => [$now->subDays(7), $now],
            '30d' => [$now->subDays(30), $now],
            'custom' => [
                $this->customFrom ? CarbonImmutable::parse($this->customFrom)->startOfDay() : $now->subDay(),
                $this->customUntil ? CarbonImmutable::parse($this->customUntil)->endOfDay() : $now,
            ],
            default => [$now->subDay(), $now],
        };
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()?->user();

        return (bool) $user?->is_super_user || (bool) $user?->can('View:Observability');
    }
}
