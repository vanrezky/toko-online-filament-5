<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\ObservabilityCluster;
use App\Modules\Platform\Tracing\Services\TraceService;
use App\Modules\Platform\Tracing\ValueObjects\TraceSummary;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Pagination\LengthAwarePaginator;

class ObservabilityTraces extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $slug = 'traces';

    protected static ?string $cluster = ObservabilityCluster::class;

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.observability-traces';

    public ?string $search = null;

    public ?string $status = null;

    public string $range = '24h';

    public ?string $operation = null;

    public ?int $durationMin = null;

    public ?int $durationMax = null;

    public bool $hasErrors = false;

    public ?string $correlationId = null;

    public int $page = 1;

    public const PER_PAGE = 25;

    public function getTitle(): string
    {
        return __('admin/observability-traces-page.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/observability-traces-page.navigation_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(4)
                ->schema([
                    TextInput::make('search')
                        ->label(__('admin/observability-traces-page.search.label'))
                        ->placeholder(__('admin/observability-traces-page.search.placeholder'))
                        ->live()
                        ->debounce(500)
                        ->columnSpan(2),
                    Select::make('range')
                        ->label(__('admin/observability-traces-page.range.label'))
                        ->options([
                            '1h' => __('admin/observability-traces-page.range.1h'),
                            '24h' => __('admin/observability-traces-page.range.24h'),
                            '7d' => __('admin/observability-traces-page.range.7d'),
                            'all' => __('admin/observability-traces-page.range.all'),
                        ])
                        ->live(),
                    Select::make('status')
                        ->label(__('admin/observability-traces-page.status.label'))
                        ->options([
                            'UNSET' => __('admin/observability-traces-page.status.UNSET'),
                            'OK' => __('admin/observability-traces-page.status.OK'),
                            'ERROR' => __('admin/observability-traces-page.status.ERROR'),
                        ])
                        ->live()
                        ->placeholder(__('admin/observability-traces-page.status.all')),
                    TextInput::make('operation')
                        ->label(__('admin/observability-traces-page.operation.label'))
                        ->placeholder(__('admin/observability-traces-page.operation.placeholder'))
                        ->live()
                        ->debounce(500),
                    TextInput::make('correlationId')
                        ->label(__('admin/observability-traces-page.correlation.label'))
                        ->placeholder(__('admin/observability-traces-page.correlation.placeholder'))
                        ->live()
                        ->debounce(500),
                    TextInput::make('durationMin')
                        ->label(__('admin/observability-traces-page.duration.min_label'))
                        ->placeholder(__('admin/observability-traces-page.duration.min_placeholder'))
                        ->numeric()
                        ->live()
                        ->debounce(500),
                    TextInput::make('durationMax')
                        ->label(__('admin/observability-traces-page.duration.max_label'))
                        ->placeholder(__('admin/observability-traces-page.duration.max_placeholder'))
                        ->numeric()
                        ->live()
                        ->debounce(500),
                    Toggle::make('hasErrors')
                        ->label(__('admin/observability-traces-page.errors.label'))
                        ->live(),
                ]),
        ]);
    }

    public function updated($property): void
    {
        if ($property !== 'page') {
            $this->page = 1;
        }
    }

    public function goToPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    /** @return array{filters: array<string, mixed>, traces: LengthAwarePaginator<int, TraceSummary>, page: int, per_page: int} */
    public function getViewData(): array
    {
        $filters = [
            'from_ns' => $this->fromNs(),
            'until_ns' => $this->untilNs(),
            'status' => $this->status,
            'operation' => $this->operation,
            'duration_min' => $this->durationMin,
            'duration_max' => $this->durationMax,
            'has_errors' => $this->hasErrors,
            'correlation_id' => $this->correlationId,
            'search' => $this->search,
        ];

        $traces = app(TraceService::class)->list(
            $filters,
            self::PER_PAGE,
            $this->page,
        );

        return [
            'filters' => $filters,
            'traces' => $traces,
            'page' => $this->page,
            'per_page' => self::PER_PAGE,
        ];
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()?->user();

        return (bool) $user?->is_super_user || (bool) $user?->can('View:Traces');
    }

    private function fromNs(): ?int
    {
        $hours = match ($this->range) {
            '1h' => 1,
            '7d' => 168,
            default => 24,
        };

        if ($this->range === 'all') {
            return null;
        }

        return (int) (now()->subHours($hours)->getTimestamp() * 1_000_000_000);
    }

    private function untilNs(): ?int
    {
        if ($this->range === 'all') {
            return null;
        }

        return (int) (now()->addMinute()->getTimestamp() * 1_000_000_000);
    }
}
