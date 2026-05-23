<?php

namespace App\Filament\Resources\TemplateSections;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\TemplateSections\Pages\ListTemplateSections;
use App\Filament\Resources\TemplateSections\Pages\CreateTemplateSection;
use App\Filament\Resources\TemplateSections\Pages\EditTemplateSection;
use App\Filament\Resources\TemplateSections\Pages\SortTemplateSections;
use App\Filament\Resources\TemplateSections\RelationManagers\FieldsRelationManager;
use App\Filament\Resources\TemplateSections\RelationManagers\ContentsRelationManager;
use App\Models\Template;
use App\Models\TemplateSection;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TemplateSectionResource extends Resource
{
    protected static ?string $model = TemplateSection::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Template Sections';
    protected static string | \UnitEnum | null $navigationGroup = 'Pengaturan';
    protected static ?string $slug = 'template-sections';
    protected static ?int $navigationSort = 99;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Section Details')
                    ->schema([
                        Select::make('template_id')
                            ->label('Template')
                            ->relationship('template', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('type')
                            ->required()
                            ->options(TemplateSection::types())
                            ->searchable(),

                        TextInput::make('name')
                            ->required()
                            ->maxLength(120),

                        TextInput::make('icon')
                            ->nullable()
                            ->placeholder('heroicon-o-photo')
                            ->helperText('Heroicon name for display in navigation'),

                        Textarea::make('description')
                            ->nullable()
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpan(2),

                Section::make('Configuration')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                        TextInput::make('order_priority')
                            ->label('Order / Priority')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Lower values appear first. Use Sortable to reorder visually.'),
                    ])
                    ->columnSpan(1),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('template.name')
                    ->label('Template')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('order_priority')
                    ->label('#')
                    ->sortable()
                    ->width(40),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('type')
                    ->colors([
                        'primary'   => 'hero',
                        'success'   => 'stories',
                        'warning'   => 'banner',
                        'info'      => 'gallery',
                        'danger'    => 'cta',
                    ])
                    ->formatStateUsing(fn (string $state) => TemplateSection::types()[$state] ?? $state),

                TextColumn::make('fields_count')
                    ->counts('fields')
                    ->label('Fields')
                    ->badge()
                    ->color('gray'),

                ToggleColumn::make('is_active')
                    ->label('Active'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order_priority', 'asc')
            ->filters([
                SelectFilter::make('template_id')
                    ->label('Template')
                    ->relationship('template', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            FieldsRelationManager::class,
            ContentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListTemplateSections::route('/'),
            'create' => CreateTemplateSection::route('/create'),
            'edit'   => EditTemplateSection::route('/{record}/edit'),
            'sort'   => SortTemplateSections::route('/{template}/sort'),
        ];
    }
}
