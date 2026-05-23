<?php

namespace App\Filament\Resources\Templates\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Actions\Action;
use App\Filament\Resources\TemplateSections\TemplateSectionResource;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\TemplateSection;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    protected static ?string $title = 'Sections';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Section Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120),

                        Select::make('type')
                            ->required()
                            ->options(TemplateSection::types())
                            ->searchable(),

                        Textarea::make('description')
                            ->nullable()
                            ->rows(2)
                            ->columnSpanFull(),

                        TextInput::make('icon')
                            ->nullable()
                            ->placeholder('heroicon-o-photo')
                            ->helperText('Heroicon name, e.g. heroicon-o-photo'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('order_priority', 'asc')
            ->reorderable('order_priority')
            ->columns([
                TextColumn::make('order_priority')
                    ->label('#')
                    ->width(40)
                    ->sortable(),

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
                        'secondary' => fn ($state) => !in_array($state, ['hero', 'stories', 'banner', 'gallery', 'cta']),
                    ])
                    ->formatStateUsing(fn (string $state) => TemplateSection::types()[$state] ?? $state),

                TextColumn::make('fields_count')
                    ->counts('fields')
                    ->label('Fields')
                    ->badge()
                    ->color('gray'),

                ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->filters([])
            ->headerActions([
                Action::make('sort_sections')
                    ->label('Sort Sections')
                    ->icon('heroicon-o-arrows-up-down')
                    ->color('gray')
                    ->url(fn () => TemplateSectionResource::getUrl(
                        'sort',
                        ['template' => $this->getOwnerRecord()->uuid]
                    )),

                CreateAction::make()
                    ->after(function (TemplateSection $record) {
                        // Set order_priority to last position
                        $maxOrder = TemplateSection::where('template_id', $record->template_id)
                            ->where('id', '!=', $record->id)
                            ->max('order_priority') ?? 0;
                        $record->update(['order_priority' => $maxOrder + 1]);
                    }),
            ])
            ->recordActions([
                Action::make('manage_fields')
                    ->label('Fields')
                    ->icon('heroicon-o-list-bullet')
                    ->url(fn (TemplateSection $record) => TemplateSectionResource::getUrl('edit', ['record' => $record->id]))
                    ->openUrlInNewTab(false),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
