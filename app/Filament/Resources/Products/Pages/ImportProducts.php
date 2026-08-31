<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Modules\ProductImport\Services\ProductImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportProducts extends Page
{
    protected static string $resource = ProductResource::class;

    protected string $view = 'filament.resources.products.pages.import-products';

    public array $data = [];

    public int $fileUploadKey = 0;

    /** @var array<string,mixed> */
    public array $preview = [];

    public function getTitle(): string
    {
        return __('admin/product-import.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadTemplate')
                ->label(__('admin/product-import.actions.download_template'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => app(ProductImportService::class)->downloadTemplate()),
            Action::make('validateImport')
                ->label(__('admin/product-import.actions.validate'))
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->action('validateImport'),
            Action::make('submitImport')
                ->label(__('admin/product-import.actions.submit'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('admin/product-import.confirmation.heading'))
                ->modalDescription(__('admin/product-import.confirmation.description'))
                ->action('submitImport')
                ->disabled(fn (): bool => empty($this->preview['token'])),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('admin/product-import.sections.upload'))
                    ->description(__('admin/product-import.upload_description'))
                    ->schema([
                        FileUpload::make('file')
                            ->label(__('admin/product-import.fields.file'))
                            ->key(fn (): string => "product-import-file-{$this->fileUploadKey}")
                            ->storeFiles(false)
                            ->required()
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->maxSize(10240),
                    ]),
            ]);
    }

    public function validateImport(): void
    {
        $this->validate();
        $fileState = $this->data['file'] ?? null;
        // Filament's FileUpload keeps its state as an array even when multiple uploads are disabled.
        $file = is_array($fileState)
            ? collect($fileState)->first(fn (mixed $item): bool => $item instanceof UploadedFile)
            : $fileState;

        if (! $file instanceof TemporaryUploadedFile) {
            Notification::make()
                ->title(__('admin/product-import.notifications.file_required'))
                ->danger()
                ->send();

            return;
        }

        $preview = app(ProductImportService::class)->validate($file, (int) auth()->id());
        $this->preview = $preview->toArray();

        if ($preview->errors !== []) {
            Notification::make()
                ->title(__('admin/product-import.notifications.validation_failed'))
                ->body(__('admin/product-import.notifications.validation_failed_body', ['count' => count($preview->errors)]))
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title(__('admin/product-import.notifications.validation_success'))
            ->body(__('admin/product-import.notifications.validation_success_body', ['count' => count($preview->rows)]))
            ->success()
            ->send();
    }

    public function submitImport(): void
    {
        $token = $this->preview['token'] ?? null;
        if (! is_string($token) || $token === '') {
            Notification::make()
                ->title(__('admin/product-import.notifications.invalid_token'))
                ->danger()
                ->send();

            return;
        }

        $count = app(ProductImportService::class)->submit($token, (int) auth()->id());
        $this->preview = [];
        $this->data = [];
        // Force a fresh file input because browsers retain the native file selection.
        $this->fileUploadKey++;
        $this->form->fill(['file' => null]);

        Notification::make()
            ->title(__('admin/product-import.notifications.submit_success'))
            ->body(__('admin/product-import.notifications.submit_success_body', ['count' => $count]))
            ->success()
            ->send();
    }

    public function hasPreview(): bool
    {
        return $this->preview !== [];
    }
}
