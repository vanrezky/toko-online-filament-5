<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\ObservabilityCluster;
use App\Modules\Platform\Observability\Services\ExecutionTimelineService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ObservabilityExecutions extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $slug = 'executions';

    protected static ?string $cluster = ObservabilityCluster::class;

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.observability-executions';

    public ?string $search = null;

    public function getTitle(): string
    {
        return __('admin/observability-executions-page.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/observability-executions-page.navigation_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(1)
                ->schema([
                    TextInput::make('search')
                        ->label(__('admin/observability-executions-page.search.label'))
                        ->placeholder(__('admin/observability-executions-page.search.placeholder'))
                        ->live()
                        ->debounce(500),
                ]),
        ]);
    }

    /** @return array{executions: array<int, array{correlation_id: string, last_event_at: ?string}>, search: ?string} */
    public function getViewData(): array
    {
        $service = app(ExecutionTimelineService::class);

        if ($this->search !== null && $this->search !== '') {
            $executions = $service->resolve($this->search);
        } else {
            $executions = $service->list();
        }

        return [
            'executions' => $executions,
            'search' => $this->search,
        ];
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()?->user();

        return (bool) $user?->is_super_user || (bool) $user?->can('View:Observability');
    }
}
