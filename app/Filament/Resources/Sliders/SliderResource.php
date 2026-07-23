<?php

namespace App\Filament\Resources\Sliders;

use App\Filament\Clusters\PromotionCluster;
use App\Constants\UploadPath;
use App\Filament\Resources\Sliders\Pages\CreateSlider;
use App\Filament\Resources\Sliders\Pages\EditSlider;
use App\Filament\Resources\Sliders\Pages\ListSliders;
use App\Models\Slider;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class SliderResource extends Resource
{
    protected static ?string $model = Slider::class;

    protected static ?string $cluster = PromotionCluster::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-photo';

    protected static ?string $slug = 'sliders';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('admin/slider-resource.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin/slider-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/slider-resource.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/slider-resource.sections.carousel_content'))
                    ->description(__('admin/slider-resource.descriptions.carousel_content'))
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('image')
                            ->label(__('admin/slider-resource.fields.image'))
                            ->maxSize(2048)
                            ->rules(['required', 'mimes:png,jpg,jpeg,webp,avif,gif', 'max:2048'])
                            ->image()
                            ->directory(UploadPath::SLIDER_UPLOAD_PATH)
                            ->helperText(__('admin/slider-resource.fields.image_helper'))
                            ->disk(getActiveDisk())
                            ->required()
                            ->imageEditor()
                            ->columnSpanFull(),
                        TextInput::make('eyebrow')
                            ->label(__('admin/slider-resource.fields.eyebrow'))
                            ->maxLength(100),
                        TextInput::make('title')
                            ->label(__('admin/slider-resource.fields.title'))
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label(__('admin/slider-resource.fields.description'))
                            ->rows(3)
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('button_label')
                            ->label(__('admin/slider-resource.fields.button_label'))
                            ->requiredWith('target_link')
                            ->maxLength(100),
                        TextInput::make('target_link')
                            ->label(__('admin/slider-resource.fields.target_link'))
                            ->requiredWith('button_label')
                            ->helperText(__('admin/slider-resource.fields.target_link_helper'))
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->columnSpan(2),
                Section::make(__('admin/slider-resource.sections.publishing'))
                    ->schema([
                        TextInput::make('sort_order')
                            ->label(__('admin/slider-resource.fields.sort_order'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        Toggle::make('is_active')
                            ->label(__('admin/slider-resource.fields.is_active'))
                            ->default(true)
                            ->required(),
                        DateTimePicker::make('start_at')
                            ->label(__('admin/slider-resource.fields.start_at'))
                            ->native(false),
                        DateTimePicker::make('end_at')
                            ->label(__('admin/slider-resource.fields.end_at'))
                            ->native(false)
                            ->after('start_at'),
                    ])
                    ->columns(1)
                    ->columnSpan(1),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->conversion('thumb')
                    ->label(__('admin/slider-resource.columns.image')),
                TextColumn::make('title')
                    ->label(__('admin/slider-resource.columns.title'))
                    ->description(fn (Slider $record): ?string => $record->eyebrow)
                    ->placeholder(__('admin/slider-resource.columns.title_placeholder'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('target_link')
                    ->label(__('admin/slider-resource.columns.target_link'))
                    ->toggleable()
                    ->limit(36),
                TextColumn::make('sort_order')
                    ->label(__('admin/slider-resource.columns.sort_order'))
                    ->sortable(),
                TextColumn::make('start_at')
                    ->label(__('admin/slider-resource.columns.start_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('end_at')
                    ->label(__('admin/slider-resource.columns.end_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_active')
                    ->label(__('admin/slider-resource.columns.is_active')),
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
            'index' => ListSliders::route('/'),
            'create' => CreateSlider::route('/create'),
            'edit' => EditSlider::route('/{record}/edit'),
        ];
    }
}
