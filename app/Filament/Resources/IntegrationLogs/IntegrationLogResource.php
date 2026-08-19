<?php

namespace App\Filament\Resources\IntegrationLogs;

use App\Filament\Resources\IntegrationLogs\Pages\ListIntegrationLogs;
use App\Filament\Resources\IntegrationLogs\Pages\ViewIntegrationLog;
use App\Modules\Platform\Integration\Models\IntegrationLog;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
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

class IntegrationLogResource extends Resource
{
    protected static ?string $model = IntegrationLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'integration-logs';

    public static function getNavigationLabel(): string
    {
        return __('admin/integration-log-resource.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin/integration-log-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/integration-log-resource.plural_model_label');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return (bool) $user?->is_super_user || (bool) $user?->can('View:IntegrationLogs');
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
        return parent::getEloquentQuery()->with('subject');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('admin/integration-log-resource.columns.timestamp'))
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),
                TextColumn::make('direction')
                    ->label(__('admin/integration-log-resource.columns.direction'))
                    ->badge()
                    ->color(fn (string $state): string => $state === IntegrationLog::DIRECTION_INBOUND ? 'info' : 'warning'),
                TextColumn::make('provider')
                    ->label(__('admin/integration-log-resource.columns.provider')),
                TextColumn::make('method')
                    ->label(__('admin/integration-log-resource.columns.method'))
                    ->placeholder('—'),
                TextColumn::make('endpoint')
                    ->label(__('admin/integration-log-resource.columns.endpoint'))
                    ->wrap(),
                TextColumn::make('status')
                    ->label(__('admin/integration-log-resource.columns.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        IntegrationLog::STATUS_SUCCESS => 'success',
                        IntegrationLog::STATUS_FAILED => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('status_code')
                    ->label(__('admin/integration-log-resource.columns.http_status'))
                    ->placeholder('—'),
                TextColumn::make('duration_ms')
                    ->label(__('admin/integration-log-resource.columns.duration'))
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '—' : ($state >= 1000 ? number_format($state / 1000, 2).'s' : $state.'ms')),
                TextColumn::make('subject_id')
                    ->label(__('admin/integration-log-resource.columns.subject'))
                    ->getStateUsing(fn (IntegrationLog $record): string => static::subjectLabel($record)),
            ])
            ->filters([
                SelectFilter::make('direction')
                    ->label(__('admin/integration-log-resource.filters.direction'))
                    ->options([
                        IntegrationLog::DIRECTION_INBOUND => __('admin/integration-log-resource.values.inbound'),
                        IntegrationLog::DIRECTION_OUTBOUND => __('admin/integration-log-resource.values.outbound'),
                    ]),
                SelectFilter::make('provider')
                    ->label(__('admin/integration-log-resource.filters.provider'))
                    ->options(fn (): array => IntegrationLog::query()
                        ->whereNotNull('provider')
                        ->distinct()
                        ->orderBy('provider')
                        ->pluck('provider', 'provider')
                        ->all()),
                SelectFilter::make('status')
                    ->label(__('admin/integration-log-resource.filters.status'))
                    ->options([
                        IntegrationLog::STATUS_PENDING => __('admin/integration-log-resource.values.pending'),
                        IntegrationLog::STATUS_SUCCESS => __('admin/integration-log-resource.values.success'),
                        IntegrationLog::STATUS_FAILED => __('admin/integration-log-resource.values.failed'),
                    ]),
                Filter::make('http_status')
                    ->schema([
                        TextInput::make('from')->numeric()->label(__('admin/integration-log-resource.filters.http_status_from')),
                        TextInput::make('until')->numeric()->label(__('admin/integration-log-resource.filters.http_status_until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, int $status): Builder => $query->where('status_code', '>=', $status))
                        ->when($data['until'] ?? null, fn (Builder $query, int $status): Builder => $query->where('status_code', '<=', $status))),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label(__('admin/integration-log-resource.filters.from')),
                        DatePicker::make('until')->label(__('admin/integration-log-resource.filters.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
                Filter::make('has_error')
                    ->label(__('admin/integration-log-resource.filters.has_error'))
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('error_message')),
                Filter::make('search')
                    ->schema([
                        TextInput::make('value')->label(__('admin/integration-log-resource.filters.search')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                            $query->where('endpoint', 'like', "%{$search}%")
                                ->orWhere('correlation_id', 'like', "%{$search}%")
                                ->orWhere('subject_id', 'like', "%{$search}%")
                                ->orWhere('error_message', 'like', "%{$search}%");
                        }),
                    )),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin/integration-log-resource.sections.summary'))
                ->schema([
                    TextEntry::make('provider')->label(__('admin/integration-log-resource.entries.provider')),
                    TextEntry::make('direction')->label(__('admin/integration-log-resource.entries.direction')),
                    TextEntry::make('method')->label(__('admin/integration-log-resource.entries.method'))->placeholder('—'),
                    TextEntry::make('endpoint')->label(__('admin/integration-log-resource.entries.endpoint'))->placeholder('—'),
                    TextEntry::make('status')->label(__('admin/integration-log-resource.entries.status'))->badge(),
                    TextEntry::make('status_code')->label(__('admin/integration-log-resource.entries.http_status'))->placeholder('—'),
                    TextEntry::make('duration_ms')->label(__('admin/integration-log-resource.entries.duration'))->formatStateUsing(fn (?int $state): string => $state === null ? '—' : $state.'ms'),
                    TextEntry::make('correlation_id')->label(__('admin/integration-log-resource.entries.correlation_id')),
                    TextEntry::make('subject_id')->label(__('admin/integration-log-resource.entries.subject'))->getStateUsing(fn (IntegrationLog $record): string => static::subjectLabel($record)),
                    TextEntry::make('started_at')->label(__('admin/integration-log-resource.entries.started_at'))->dateTime('d M Y, H:i:s'),
                    TextEntry::make('finished_at')->label(__('admin/integration-log-resource.entries.finished_at'))->dateTime('d M Y, H:i:s')->placeholder('—'),
                ])->columns(2),
            Section::make(__('admin/integration-log-resource.sections.request'))
                ->schema([
                    TextEntry::make('request_headers')->label(__('admin/integration-log-resource.entries.headers'))->getStateUsing(fn (IntegrationLog $record): string => static::json($record->request_headers))->columnSpanFull(),
                    TextEntry::make('request_body')->label(__('admin/integration-log-resource.entries.body'))->getStateUsing(fn (IntegrationLog $record): string => static::json($record->request_body))->columnSpanFull(),
                ]),
            Section::make(__('admin/integration-log-resource.sections.response'))
                ->schema([
                    TextEntry::make('response_headers')->label(__('admin/integration-log-resource.entries.headers'))->getStateUsing(fn (IntegrationLog $record): string => static::json($record->response_headers))->columnSpanFull(),
                    TextEntry::make('response_body')->label(__('admin/integration-log-resource.entries.body'))->getStateUsing(fn (IntegrationLog $record): string => static::json($record->response_body))->columnSpanFull(),
                ]),
            Section::make(__('admin/integration-log-resource.sections.error'))
                ->schema([
                    TextEntry::make('error_class')->label(__('admin/integration-log-resource.entries.error_class'))->placeholder('—'),
                    TextEntry::make('error_message')->label(__('admin/integration-log-resource.entries.error_message'))->placeholder('—')->columnSpanFull(),
                ]),
        ])->columns(1);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIntegrationLogs::route('/'),
            'view' => ViewIntegrationLog::route('/{record}'),
        ];
    }

    private static function subjectLabel(IntegrationLog $record): string
    {
        if (! $record->subject_type || ! $record->subject_id) {
            return '—';
        }

        $identifier = $record->subject?->getAttribute('code') ?? $record->subject?->getKey() ?? $record->subject_id;

        return class_basename($record->subject_type).' #'.$identifier;
    }

    /** @param array<string|int, mixed>|null $value */
    private static function json(?array $value): string
    {
        return $value ? (json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—') : '—';
    }
}
