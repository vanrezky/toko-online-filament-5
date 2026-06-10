<?php

namespace App\Filament\Resources\EmailTemplates;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\EmailTemplates\Pages\ListEmailTemplates;
use App\Filament\Resources\EmailTemplates\Pages\CreateEmailTemplate;
use App\Filament\Resources\EmailTemplates\Pages\EditEmailTemplate;
use App\Models\EmailTemplate;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;


class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-envelope';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'email-templates';

    public static function getNavigationLabel(): string
    {
        return __('admin/email-template-resource.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/email-template-resource.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('admin/email-template-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/email-template-resource.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/email-template-resource.sections.template_information'))
                    ->schema([
                        TextInput::make('code')
                            ->label(__('admin/email-template-resource.fields.code'))
                            ->required()
                            ->maxLength(100)
                            ->unique(ignorable: fn ($record) => $record)
                            ->disabled(fn ($record) => $record !== null)
                            ->helperText(__('admin/email-template-resource.fields.code_helper'))
                            ->columnSpan(1),
                        TextInput::make('name')
                            ->label(__('admin/email-template-resource.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        Toggle::make('is_active')
                            ->label(__('admin/email-template-resource.fields.is_active'))
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(1),
                        Toggle::make('send_to_admin')
                            ->label(__('admin/email-template-resource.fields.send_to_admin'))
                            ->default(false)
                            ->inline(false)
                            ->helperText(__('admin/email-template-resource.fields.send_to_admin_helper'))
                            ->columnSpan(1),
                    ])->columns(2),

                Section::make(__('admin/email-template-resource.sections.header_settings'))
                    ->schema([
                        TextInput::make('header_title')
                            ->label(__('admin/email-template-resource.fields.header_title'))
                            ->maxLength(255)
                            ->helperText(__('admin/email-template-resource.fields.header_title_helper')),
                        ColorPicker::make('header_gradient')
                            ->label(__('admin/email-template-resource.fields.header_gradient'))
                            ->helperText(__('admin/email-template-resource.fields.header_gradient_helper'))
                            ->columnSpan(1),
                    ])->columns(2),

                Section::make(__('admin/email-template-resource.sections.email_content'))
                    ->schema([
                        TextInput::make('subject')
                            ->label(__('admin/email-template-resource.fields.subject'))
                            ->required()
                            ->maxLength(255)
                            ->helperText(__('admin/email-template-resource.fields.subject_helper'))
                            ->columnSpanFull()
                            ->maxLength(255),
                        View::make('filament.forms.placeholders'),

                        RichEditor::make('body')
                            ->label(__('admin/email-template-resource.fields.body'))
                            ->required()
                            ->maxLength(65535)
                            ->helperText(__('admin/email-template-resource.fields.body_helper'))
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'alignStart',
                                'alignCenter',
                                'alignEnd',
                                'orderedList',
                                'bulletList',
                                'link',
                            ])
                            ->columnSpanFull(),
                    ])->columns(1),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('admin/email-template-resource.columns.code'))
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('name')
                    ->label(__('admin/email-template-resource.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subject')
                    ->label(__('admin/email-template-resource.columns.subject'))
                    ->limit(50),
                IconColumn::make('is_active')
                    ->label(__('admin/email-template-resource.columns.is_active'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                IconColumn::make('send_to_admin')
                    ->label(__('admin/email-template-resource.columns.send_to_admin'))
                    ->boolean()
                    ->trueIcon('heroicon-o-bell')
                    ->falseIcon('heroicon-o-bell-slash'),
                TextColumn::make('updated_at')
                    ->label(__('admin/email-template-resource.columns.updated_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('is_active')
                    ->label(__('admin/email-template-resource.fields.is_active'))
                    ->query(fn ($query) => $query->where('is_active', true))
                    ->toggle(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailTemplates::route('/'),
            'create' => CreateEmailTemplate::route('/create'),
            'edit' => EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
