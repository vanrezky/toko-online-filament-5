<?php

namespace App\Filament\Resources\TemplateSections\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Columns\TextColumn;
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

class ContentsRelationManager extends RelationManager
{
    protected static string $relationship = 'contents';

    protected static ?string $title = 'Content Values';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('field_id')
                            ->label('Field')
                            ->options(function (RelationManager $livewire) {
                                return TemplateSectionField::where('section_id', $livewire->getOwnerRecord()->id)
                                    ->orderBy('order_priority')
                                    ->pluck('label', 'id');
                            })
                            ->required()
                            ->unique(
                                table: 'template_section_contents',
                                column: 'field_id',
                                ignoreRecord: true,
                                modifyRuleUsing: function ($rule, RelationManager $livewire) {
                                    return $rule->where('section_id', $livewire->getOwnerRecord()->id);
                                }
                            )
                            ->reactive()
                            ->searchable(),

                        Textarea::make('value')
                            ->label('Value')
                            ->nullable()
                            ->rows(3)
                            ->columnSpanFull(),

                        KeyValue::make('meta')
                            ->label('Meta / Extra Data')
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('field_id')
            ->columns([
                TextColumn::make('field.key')
                    ->label('Field Key')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('field.label')
                    ->label('Field Label'),

                TextColumn::make('field.type')
                    ->label('Type')
                    ->badge(),

                TextColumn::make('value')
                    ->label('Value')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->value),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make(),
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
