<?php

namespace App\Filament\Resources\EmailLogs;

use App\Filament\Clusters\MonitoringCluster;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use App\Filament\Resources\EmailLogs\Pages\ListEmailLogs;
use App\Filament\Resources\EmailLogs\Pages\ViewEmailLog;
use App\Models\EmailLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmailLogResource extends Resource
{
    protected static ?string $model = EmailLog::class;

    protected static ?string $cluster = MonitoringCluster::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-envelope-open';
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'email-logs';

    public static function getNavigationLabel(): string
    {
        return __('admin/email-log-resource.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin/email-log-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/email-log-resource.plural_model_label');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
            TextColumn::make('template_code')
                ->label(__('admin/email-log-resource.columns.template_code'))
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('recipient_email')
                    ->label(__('admin/email-log-resource.columns.recipient_email'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subject')
                    ->label(__('admin/email-log-resource.columns.subject'))
                    ->limit(50),
                BadgeColumn::make('status')
                    ->label(__('admin/email-log-resource.columns.status'))
                    ->colors([
                        'success' => 'sent',
                        'danger' => 'failed',
                        'warning' => 'pending',
                    ])
                    ->formatStateUsing(fn($state) => __('admin/email-log-resource.status_options.' . $state)),
            TextColumn::make('error_message')
                ->label(__('admin/email-log-resource.columns.error_message'))
                ->limit(50)
                ->tooltip(fn($state) => $state)
                ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sent_at')
                    ->label(__('admin/email-log-resource.columns.sent_at'))
                    ->dateTime()
                    ->sortable(),
            TextColumn::make('created_at')
                ->label(__('admin/email-log-resource.columns.created_at'))
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin/email-log-resource.filters.status'))
                    ->options([
                        'pending' => __('admin/email-log-resource.status_options.pending'),
                        'sent' => __('admin/email-log-resource.status_options.sent'),
                        'failed' => __('admin/email-log-resource.status_options.failed'),
                    ]),
                SelectFilter::make('template_code')
                    ->label(__('admin/email-log-resource.filters.template_code'))
                    ->relationship('emailTemplate', 'code')
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailLogs::route('/'),
            'view' => ViewEmailLog::route('/{record}'),
        ];
    }
}
