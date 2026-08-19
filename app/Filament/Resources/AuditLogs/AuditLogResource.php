<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Models\InstallmentPayment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Modules\Platform\Audit\Services\AuditLogService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class AuditLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'audit-logs';

    public static function getNavigationLabel(): string
    {
        return __('admin/audit-log-resource.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin/audit-log-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/audit-log-resource.plural_model_label');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return (bool) $user?->is_super_user || (bool) $user?->can('View:AuditLogs');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['causer', 'subject']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('admin/audit-log-resource.columns.timestamp'))
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('causer')
                    ->label(__('admin/audit-log-resource.columns.actor'))
                    ->getStateUsing(fn (Activity $record): string => app(AuditLogService::class)->actorLabel($record))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHasMorph(
                        'causer',
                        [User::class],
                        fn (Builder $causerQuery): Builder => $causerQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"),
                    )),
                TextColumn::make('event')
                    ->label(__('admin/audit-log-resource.columns.action'))
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('subject_type')
                    ->label(__('admin/audit-log-resource.columns.subject_type'))
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—'),
                TextColumn::make('subject_id')
                    ->label(__('admin/audit-log-resource.columns.subject'))
                    ->getStateUsing(fn (Activity $record): string => app(AuditLogService::class)->subjectLabel($record))
                    ->searchable(),
                TextColumn::make('description')
                    ->label(__('admin/audit-log-resource.columns.description'))
                    ->searchable()
                    ->wrap(),
            ])
            ->filters([
                Filter::make('actor')
                    ->schema([
                        Select::make('causer_id')
                            ->label(__('admin/audit-log-resource.filters.actor'))
                            ->options(fn (): array => User::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable(),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['causer_id'] ?? null,
                        fn (Builder $query, $id): Builder => $query
                            ->where('causer_type', User::class)
                            ->where('causer_id', $id),
                    )),
                SelectFilter::make('event')
                    ->label(__('admin/audit-log-resource.filters.action'))
                    ->options([
                        'created' => 'created',
                        'updated' => 'updated',
                        'deleted' => 'deleted',
                        'business' => 'business',
                    ]),
                SelectFilter::make('subject_type')
                    ->label(__('admin/audit-log-resource.filters.subject_type'))
                    ->options([
                        Product::class => class_basename(Product::class),
                        Transaction::class => class_basename(Transaction::class),
                        InstallmentPayment::class => class_basename(InstallmentPayment::class),
                    ]),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label(__('admin/audit-log-resource.filters.from')),
                        DatePicker::make('until')->label(__('admin/audit-log-resource.filters.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
                Filter::make('correlation_id')
                    ->schema([
                        TextInput::make('correlation_id')
                            ->label(__('admin/audit-log-resource.filters.correlation_id')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['correlation_id'] ?? null),
                        fn (Builder $query, string $value): Builder => $query->where('properties->context->correlation_id', 'like', "%{$value}%"),
                    )),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin/audit-log-resource.sections.activity'))
                ->schema([
                    TextEntry::make('causer')
                        ->label(__('admin/audit-log-resource.entries.actor'))
                        ->getStateUsing(fn (Activity $record): string => app(AuditLogService::class)->actorLabel($record)),
                    TextEntry::make('event')->label(__('admin/audit-log-resource.entries.action'))->placeholder('—'),
                    TextEntry::make('subject_id')
                        ->label(__('admin/audit-log-resource.entries.subject'))
                        ->getStateUsing(fn (Activity $record): string => app(AuditLogService::class)->subjectLabel($record)),
                    TextEntry::make('created_at')->label(__('admin/audit-log-resource.entries.timestamp'))->dateTime('d M Y, H:i:s'),
                    TextEntry::make('description')->label(__('admin/audit-log-resource.entries.description'))->columnSpanFull(),
                ])->columns(2),
            Section::make(__('admin/audit-log-resource.sections.metadata'))
                ->schema([
                    TextEntry::make('metadata.correlation_id')->label(__('admin/audit-log-resource.entries.correlation_id'))->getStateUsing(fn (Activity $record): string => (string) data_get($record->properties?->all(), 'context.correlation_id', '—')),
                    TextEntry::make('metadata.ip')->label(__('admin/audit-log-resource.entries.ip'))->getStateUsing(fn (Activity $record): string => (string) data_get($record->properties?->all(), 'context.ip', '—')),
                    TextEntry::make('metadata.method')->label(__('admin/audit-log-resource.entries.method'))->getStateUsing(fn (Activity $record): string => (string) data_get($record->properties?->all(), 'context.method', '—')),
                    TextEntry::make('metadata.url')->label(__('admin/audit-log-resource.entries.url'))->getStateUsing(fn (Activity $record): string => (string) data_get($record->properties?->all(), 'context.url', '—'))->columnSpanFull(),
                    TextEntry::make('metadata.user_agent')->label(__('admin/audit-log-resource.entries.user_agent'))->getStateUsing(fn (Activity $record): string => (string) data_get($record->properties?->all(), 'context.user_agent', '—'))->columnSpanFull(),
                ])->columns(2),
            Section::make(__('admin/audit-log-resource.sections.changes'))
                ->schema([
                    RepeatableEntry::make('changes')
                        ->getStateUsing(fn (Activity $record): array => app(AuditLogService::class)->changes($record))
                        ->schema([
                            TextEntry::make('field')->label(__('admin/audit-log-resource.entries.field'))->weight('bold'),
                            TextEntry::make('old')->label(__('admin/audit-log-resource.entries.old'))->formatStateUsing(fn (mixed $state): string => app(AuditLogService::class)->displayValue($state)),
                            TextEntry::make('new')->label(__('admin/audit-log-resource.entries.new'))->formatStateUsing(fn (mixed $state): string => app(AuditLogService::class)->displayValue($state)),
                        ])->columns(3)
                        ->placeholder(__('admin/audit-log-resource.empty_changes')),
                ]),
        ])->columns(1);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'view' => ViewAuditLog::route('/{record}'),
        ];
    }
}
