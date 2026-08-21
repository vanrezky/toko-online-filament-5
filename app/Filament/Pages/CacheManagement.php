<?php

namespace App\Filament\Pages;

use App\Modules\Platform\Audit\Services\AuditLogService;
use App\Modules\Platform\Cache\Services\CacheManagementService;
use App\Modules\Platform\Cache\ValueObjects\CacheManagementSnapshot;
use App\Services\CacheService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class CacheManagement extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.cache-management';

    public function getTitle(): string
    {
        return __('admin/cache-management-page.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/cache-management-page.navigation_label');
    }

    public function booted(): void
    {
        // Register body actions before Livewire resolves a clicked action.
        $this->getGroupCacheActions();
    }

    protected function getHeaderActions(): array
    {
        return [$this->buildGlobalCacheAction()];
    }

    /** @return array<int, Action> */
    public function getGroupCacheActions(): array
    {
        $actions = [];
        foreach (CacheService::managedGroups() as $group => $label) {
            $actions[] = Action::make('clear'.str_replace('-', '', ucwords($group, '-')))
                ->label(__('admin/cache-management-page.actions.clear_group', ['group' => $label]))
                ->icon('heroicon-o-trash')
                ->color('gray')
                ->modal()
                ->requiresConfirmation()
                ->modalHeading(__('admin/cache-management-page.confirmation.group_heading', ['group' => $label]))
                ->modalDescription(__('admin/cache-management-page.confirmation.group_description', ['group' => $label]))
                ->modalSubmitActionLabel(__('admin/cache-management-page.confirmation.submit'))
                ->modalCancelActionLabel(__('admin/cache-management-page.confirmation.cancel'))
                ->visible(fn (): bool => static::canManage())
                ->action(fn () => $this->clearManagedGroup($group));
        }

        return array_map(fn (Action $action): Action => $this->cacheAction($action), $actions);
    }

    private function buildGlobalCacheAction(): Action
    {
        return Action::make('clearManagedCache')
            ->label(__('admin/cache-management-page.actions.clear'))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->modal()
            ->requiresConfirmation()
            ->modalHeading(__('admin/cache-management-page.confirmation.heading'))
            ->modalDescription(__('admin/cache-management-page.confirmation.description'))
            ->modalSubmitActionLabel(__('admin/cache-management-page.confirmation.submit'))
            ->modalCancelActionLabel(__('admin/cache-management-page.confirmation.cancel'))
            ->visible(fn (): bool => static::canManage())
            ->action(fn () => $this->clearManagedCache());
    }

    /** @return array{snapshot: CacheManagementSnapshot} */
    public function getViewData(): array
    {
        return [
            'snapshot' => app(CacheManagementService::class)->snapshot(),
        ];
    }

    public function clearManagedCache(): void
    {
        abort_unless(static::canManage(), 403);

        $this->finishClear(
            app(CacheManagementService::class)->clearManaged(),
            'managed_application_cache',
            __('admin/cache-management-page.notifications.cleared'),
        );
    }

    public function clearManagedGroup(string $group): void
    {
        abort_unless(static::canManage(), 403);

        $groups = CacheService::managedGroups();
        abort_unless(array_key_exists($group, $groups), 404);

        $this->finishClear(
            app(CacheManagementService::class)->clearGroup($group),
            $group,
            __('admin/cache-management-page.notifications.group_cleared', ['group' => $groups[$group]]),
        );
    }

    /** @param array{cleared: array<int, string>, failed: array<int, string>} $result */
    private function finishClear(array $result, string $scope, string $successMessage): void
    {
        $attributes = [
            'scope' => $scope,
            'cleared_groups' => $result['cleared'],
            'failed_groups' => $result['failed'],
            'outcome' => $result['failed'] === [] ? 'success' : 'partial_failure',
        ];

        app(AuditLogService::class)->logOperationalAction('Application cache cleared', $attributes);

        if ($result['failed'] !== []) {
            Notification::make()
                ->title(__('admin/cache-management-page.notifications.partial_failure'))
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title($successMessage)
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()?->user();

        return (bool) $user?->is_super_user
            || (bool) $user?->can('View:CacheManagement')
            || (bool) $user?->can('Manage:CacheManagement');
    }

    public static function canManage(): bool
    {
        $user = Filament::auth()?->user();

        return (bool) $user?->is_super_user || (bool) $user?->can('Manage:CacheManagement');
    }
}
