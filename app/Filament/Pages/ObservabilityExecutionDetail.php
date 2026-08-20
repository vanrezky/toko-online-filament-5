<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\ObservabilityCluster;
use App\Modules\Platform\Observability\Services\ExecutionTimelineService;
use App\Modules\Platform\Observability\ValueObjects\ExecutionTimeline;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class ObservabilityExecutionDetail extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $slug = 'executions/{correlation}';

    protected static ?string $cluster = ObservabilityCluster::class;

    protected string $view = 'filament.pages.observability-execution-detail';

    public ?string $correlation = null;

    public function getTitle(): string
    {
        return __('admin/observability-execution-detail-page.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/observability-execution-detail-page.navigation_label');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    /** @return array{timeline: ExecutionTimeline} */
    public function getViewData(): array
    {
        return [
            'timeline' => app(ExecutionTimelineService::class)->timeline($this->correlation),
        ];
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()?->user();

        return (bool) $user?->is_super_user || (bool) $user?->can('View:Observability');
    }
}
