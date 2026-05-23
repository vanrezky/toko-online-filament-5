<?php

namespace App\Filament\Resources\TemplateSections\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\KeyValue;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\TemplateSectionField;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class FieldsRelationManager extends RelationManager
{
    protected static string $relationship = 'fields';

    protected static ?string $title = 'Field Definitions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('key')
                            ->required()
                            ->alphaDash()
                            ->maxLength(80)
                            ->helperText('Unique field key, e.g. title, subtitle, image_url, button_link')
                            ->unique(
                                table: 'template_section_fields',
                                column: 'key',
                                ignoreRecord: true,
                                modifyRuleUsing: function ($rule, RelationManager $livewire) {
                                    return $rule->where('section_id', $livewire->getOwnerRecord()->id);
                                }
                            ),

                        TextInput::make('label')
                            ->required()
                            ->maxLength(120),

                        Select::make('type')
                            ->required()
                            ->options(TemplateSectionField::fieldTypes())
                            ->default('text')
                            ->reactive(),

                        TextInput::make('placeholder')
                            ->nullable()
                            ->maxLength(255),

                        Textarea::make('default_value')
                            ->nullable()
                            ->rows(2)
                            ->columnSpanFull(),

                        KeyValue::make('options')
                            ->label('Select Options (key → label)')
                            ->nullable()
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('type') === 'select'),

                        Toggle::make('is_required')
                            ->label('Required')
                            ->default(false),

                        TextInput::make('order_priority')
                            ->label('Order')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->defaultSort('order_priority', 'asc')
            ->reorderable('order_priority')
            ->columns([
                TextColumn::make('order_priority')
                    ->label('#')
                    ->width(40)
                    ->sortable(),

                TextColumn::make('key')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('label')
                    ->searchable(),

                BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'text',
                        'warning' => 'image',
                        'info'    => 'url',
                        'success' => 'toggle',
                        'danger'  => 'richtext',
                    ]),

                IconColumn::make('is_required')
                    ->label('Required')
                    ->boolean(),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make()
                    ->after(function (TemplateSectionField $record) {
                        $maxOrder = TemplateSectionField::where('section_id', $record->section_id)
                            ->where('id', '!=', $record->id)
                            ->max('order_priority') ?? 0;
                        $record->update(['order_priority' => $maxOrder + 1]);
                    }),
            ])
            ->recordActions([
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
