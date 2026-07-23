<?php

namespace App\Filament\Resources\ContactMessages;

use App\Filament\Clusters\MonitoringCluster;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Models\ContactMessage;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static ?string $cluster = MonitoringCluster::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-envelope';
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'contact-messages';

    public static function getNavigationLabel(): string
    {
        return __('admin/contact-message-resource.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin/contact-message-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/contact-message-resource.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ContactMessage::unread()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/contact-message-resource.sections.message_details'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin/contact-message-resource.fields.name'))
                            ->disabled(),
                        TextInput::make('email')
                            ->label(__('admin/contact-message-resource.fields.email'))
                            ->disabled(),
                        TextInput::make('subject')
                            ->label(__('admin/contact-message-resource.fields.subject'))
                            ->disabled(),
                        Textarea::make('message')
                            ->label(__('admin/contact-message-resource.fields.message'))
                            ->disabled()
                            ->columnSpanFull()
                            ->rows(8),
                    ])->columns(2),
                Section::make(__('admin/contact-message-resource.sections.status'))
                    ->schema([
                        Toggle::make('is_read')
                            ->label(__('admin/contact-message-resource.fields.is_read')),
                        DateTimePicker::make('read_at')
                            ->disabled()
                            ->label(__('admin/contact-message-resource.fields.read_at')),
                        DateTimePicker::make('created_at')
                            ->disabled()
                            ->label(__('admin/contact-message-resource.fields.created_at')),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_read')
                    ->boolean()
                    ->label(__('admin/contact-message-resource.columns.is_read'))
                    ->trueIcon('heroicon-o-envelope-open')
                    ->falseIcon('heroicon-o-envelope')
                    ->trueColor('success')
                    ->falseColor('warning'),
                TextColumn::make('name')
                    ->label(__('admin/contact-message-resource.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('admin/contact-message-resource.columns.email'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('subject')
                    ->label(__('admin/contact-message-resource.columns.subject'))
                    ->searchable()
                    ->limit(40),
                TextColumn::make('message')
                    ->label(__('admin/contact-message-resource.columns.message'))
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->label(__('admin/contact-message-resource.columns.created_at')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('is_read')
                    ->label(__('admin/contact-message-resource.filters.is_read'))
                    ->options([
                        '0' => __('admin/contact-message-resource.filters.unread'),
                        '1' => __('admin/contact-message-resource.filters.read'),
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('admin/contact-message-resource.empty_state.heading'))
            ->emptyStateDescription(__('admin/contact-message-resource.empty_state.description'));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactMessages::route('/'),
            'view' => ViewContactMessage::route('/{record}'),
        ];
    }
}
