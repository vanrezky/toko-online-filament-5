<?php

namespace App\Filament\Resources\Sliders;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Sliders\Pages\ListSliders;
use App\Filament\Resources\Sliders\Pages\CreateSlider;
use App\Filament\Resources\Sliders\Pages\EditSlider;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\IconColumn;
use App\Constants\UploadPath;
use App\Models\Slider;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;

class SliderResource extends Resource
{
    protected static ?string $model = Slider::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationLabel = 'Home Slider';
    protected static string | \UnitEnum | null $navigationGroup = 'Promo';
    protected static ?string $slug = 'sliders';
    protected static ?int $navigationSort = 2;
    static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Slider Information')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('image')
                            ->label(__('Slider Image'))
                            ->maxSize(2048)
                            ->rules(['required', 'mimes:png,jpg,jpeg,webp,gif', 'max:2048'])
                            ->image()
                            ->directory(UploadPath::SLIDER_UPLOAD_PATH)
                            ->helperText(__('Maximum size is 2MB'))
                            ->disk(getActiveDisk())
                            ->required()
                            ->imageEditor()
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->string()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('target_link')
                            ->label('Link')
                            ->rules('nullable')
                            ->maxLength(255),
                        Select::make('target_anchor')
                            ->label('Target')
                            ->required()
                            ->options([
                                '_self' => 'Same Tab',
                                '_blank' => 'New Tab'
                            ])
                            ->default('_self')
                            ->native(false),


                    ])->columnSpan(2)->columns(2),
                Section::make('Other')
                    ->schema([
                        DatePicker::make('start_at')->native(false),
                        DatePicker::make('end_at')->native(false),
                        Toggle::make('is_active')
                            ->required()
                            ->default(true),
                    ])->columnSpan(1)->columns(2),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')->conversion('thumb'),
                TextColumn::make('description')->searchable(),
                TextColumn::make('target_link')->label('Link')->searchable(),
                TextColumn::make('target_anchor')->label('Target')->sortable(),
                TextColumn::make('start_at')->sortable()
                    ->date()
                    ->sortable(),
                TextColumn::make('end_at')
                    ->date()
                    ->sortable(),
                self::getIsActiveColumn(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSliders::route('/'),
            'create' => CreateSlider::route('/create'),
            'edit' => EditSlider::route('/{record}/edit'),
        ];
    }

    public static function getIsActiveColumn()
    {
        if (self::shouldCanUpdate()) {
            return ToggleColumn::make('is_active')
                ->afterStateUpdated(fn() => notification(__('Activation status updated successfully'), 'success'))
                ->label(__('Active'));
        }

        return IconColumn::make('is_active')->boolean()->label(__('Active'));
    }

    public static function shouldCanUpdate(): bool
    {
        return auth()->user()->can('update_slider');
    }
}
