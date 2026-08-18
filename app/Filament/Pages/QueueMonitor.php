<?php

namespace App\Filament\Pages;

use App\Modules\Platform\Queue\Services\QueueMonitorService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class QueueMonitor extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static string|\UnitEnum|null $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.queue-monitor';

    public function getTitle(): string
    {
        return __('admin/queue-monitor-page.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/queue-monitor-page.navigation_label');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openHorizon')
                ->label(__('admin/queue-monitor-page.actions.open_horizon'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(url(config('horizon.path')))
                ->openUrlInNewTab(),
        ];
    }

    public function getViewData(): array
    {
        return [
            'snapshot' => app(QueueMonitorService::class)->snapshot(),
        ];
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()?->user();

        return (bool) $user?->is_super_user || (bool) $user?->can('View:QueueMonitor');
    }
}
