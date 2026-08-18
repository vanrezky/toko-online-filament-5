<?php

namespace App\Filament\Pages;

use App\Modules\Platform\Health\Services\HealthMonitorService;
use App\Modules\Platform\Health\ValueObjects\HealthMonitorSnapshot;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SystemHealth extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static string|\UnitEnum|null $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.system-health';

    public function getTitle(): string
    {
        return __('admin/system-health-page.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/system-health-page.navigation_label');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshHealth')
                ->label(__('admin/system-health-page.actions.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->action('refreshHealth'),
        ];
    }

    public function refreshHealth(): void
    {
        app(HealthMonitorService::class)->refresh();

        Notification::make()
            ->title(__('admin/system-health-page.notifications.refreshed'))
            ->success()
            ->send();
    }

    /** @return array{snapshot: HealthMonitorSnapshot} */
    public function getViewData(): array
    {
        return ['snapshot' => app(HealthMonitorService::class)->summary()];
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()?->user();

        return (bool) $user?->is_super_user || (bool) $user?->can('View:SystemHealth');
    }
}
