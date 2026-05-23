<?php

namespace App\Filament\Resources\Templates;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Templates\Pages\ListTemplates;
use App\Filament\Resources\Templates\Pages\CreateTemplate;
use App\Filament\Resources\Templates\Pages\EditTemplate;
use App\Filament\Resources\Templates\RelationManagers\SectionsRelationManager;
use App\Models\Template;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-paint-brush';
    protected static ?string $navigationLabel = 'Templates';
    protected static string | \UnitEnum | null $navigationGroup = 'Pengaturan';
    protected static ?string $slug = 'templates';
    protected static ?int $navigationSort = 99;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) =>
                                $set('code', Str::slug($state ?? '', '_'))
                            ),

                        TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(80)
                            ->helperText('Auto-generated from name. Must be unique.')
                            ->alphaDash(),

                        Textarea::make('description')
                            ->nullable()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpan(2),

                Section::make('Color Scheme')
                    ->schema([
                        ColorPicker::make('color_scheme.primary')
                            ->label('Primary Color')
                            ->default('#3B82F6'),

                        ColorPicker::make('color_scheme.secondary')
                            ->label('Secondary Color')
                            ->default('#10B981'),

                        ColorPicker::make('color_scheme.accent')
                            ->label('Accent Color')
                            ->default('#F59E0B'),

                        ColorPicker::make('color_scheme.background')
                            ->label('Background Color')
                            ->default('#FFFFFF'),

                        ColorPicker::make('color_scheme.text')
                            ->label('Text Color')
                            ->default('#111827'),
                    ])
                    ->columns(1)
                    ->columnSpan(1),

                Section::make('Settings')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(false)
                            ->helperText('Only one template can be active at a time.'),

                        TextInput::make('thumbnail')
                            ->label('Thumbnail URL')
                            ->url()
                            ->nullable()
                            ->placeholder('https://example.com/thumbnail.png')
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpan(1),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('code')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('sections_count')
                    ->counts('sections')
                    ->label('Sections')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                ColorColumn::make('color_scheme.primary')
                    ->label('Primary Color')
                    ->tooltip(fn (Template $record) => $record->color_scheme['primary'] ?? null),

                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->afterStateUpdated(fn () => notification('Template status updated.', 'success')),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
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
            SectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListTemplates::route('/'),
            'create' => CreateTemplate::route('/create'),
            'edit'   => EditTemplate::route('/{record}/edit'),
        ];
    }
}
