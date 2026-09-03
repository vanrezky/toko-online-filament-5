<?php

namespace App\Filament\Pages;

use App\Services\R2StorageTester;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class R2StorageTesterPage extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cloud-arrow-up';

    protected static string|\UnitEnum|null $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'r2-storage-tester';

    protected string $view = 'filament.pages.r2-storage-tester';

    public function getTitle(): string
    {
        return __('admin/r2-storage-tester-page.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/r2-storage-tester-page.navigation_label');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testR2')
                ->label(__('admin/r2-storage-tester-page.actions.test'))
                ->icon('heroicon-o-play')
                ->action('testR2'),
        ];
    }

    public function testR2(): void
    {
        abort_unless(static::canAccess(), 403);

        $result = app(R2StorageTester::class)->test();

        if (! $result['success']) {
            Notification::make()
                ->title(__('admin/r2-storage-tester-page.notifications.failed'))
                ->body(__('admin/r2-storage-tester-page.notifications.failed_body'))
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title(__('admin/r2-storage-tester-page.notifications.success'))
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()?->user();

        return (bool) $user?->is_super_user || (bool) $user?->can('View:R2StorageTester');
    }
}
