---
name: filament-guide
description: Guide for building Filament v3 admin resources in this Laravel project. Use this when creating new Filament Resources, Forms, Tables, Pages, or RelationManagers.
license: MIT
compatibility: opencode
metadata:
  category: framework
  framework: filament
  version: "3"
---

## What I do

I help create and modify Filament v3 admin panel code following the project's established conventions.

## Project Filament Conventions

### Resource Structure
All resources extend `Filament\Resources\Resource` and live in `App\Filament\Resources\`.

```php
namespace App\Filament\Resources;

use App\Models\YourModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class YourResource extends Resource
{
    protected static ?string $model = YourModel::class;
    protected static ?string $navigationIcon = 'heroicon-o-...';
    protected static ?string $navigationGroup = 'GroupName';
    protected static ?string $navigationLabel = 'Label';
    protected static ?string $slug = 'your-slug';
    protected static ?int $navigationSort = 1;
}
```

### Navigation Groups Used in This Project
- **Product** — Products, Categories, Warehouses
- **Transaction** — Transactions, Orders
- **Customer** — Customers, Resellers, Balances
- **Promo** — Flashsales, Promotions, Vouchers, Sliders
- **Blog** — Blog Posts, Blog Categories, Pages
- **Master** — Provinces, Districts, Sub-Districts, Villages, Countries, FAQs
- **Setting** — Users, Email Templates
- **Appearance** — Templates, Template Sections
- **Logs** — Email Logs, Exceptions

### Common Form Patterns

**Tabs Layout (most common):**
```php
Tabs::make()
    ->tabs([
        Tabs\Tab::make('Main')->schema([...]),
        Tabs\Tab::make('SEO')->schema([...]),
    ])->columnSpanFull()
```

**Sections:**
```php
Forms\Components\Section::make('Section Title')
    ->schema([...])
    ->columns(2)
```

**Common Components:**
- `TextInput::make('name')->required()`
- `Select::make('category_id')->relationship('category', 'name')->searchable()->preload()`
- `Toggle::make('is_active')`
- `RichEditor::make('description')`
- `DatePicker::make('published_at')->native(false)`
- `DateTimePicker::make('created_at')`

**File Upload (Spatie Media Library):**
```php
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

SpatieMediaLibraryFileUpload::make('images')
    ->image()
    ->imageEditor()
    ->multiple()
    ->reorderable()
    ->maxFiles(5)
    ->optimize('webp')
    ->directory(UploadPath::YOUR_PATH)
    ->disk(config('filesystems.upload_disk'))
```

### SEO Schema Helpers
Always use these for resources with SEO:

```php
use App\Filament\Resources\Schema\TitleSchema;
use App\Filament\Resources\Schema\MetaSchema;

// In form schema:
TitleSchema::title('name')
    ->required()
    ->live(onBlur: true),
TitleSchema::slug(),
TitleSchema::hidden(),  // Hidden field for is_slug_changed_manually
MetaSchema::get(),      // Meta title, description, keywords
```

### Table Patterns

**Common Columns:**
```php
Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
Tables\Columns\BadgeColumn::make('status')->colors([...]),
Tables\Columns\IconColumn::make('is_active')->boolean(),
Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
```

**Toggle Column (with permission check):**
```php
public static function getIsActiveColumn()
{
    if (self::shouldCanUpdate()) {
        return Tables\Columns\ToggleColumn::make('is_active')
            ->afterStateUpdated(fn () => notification(__('Updated'), 'success'));
    }
    return Tables\Columns\IconColumn::make('is_active')->boolean();
}

public static function shouldCanUpdate(): bool
{
    return auth()->user()->can('update_resource_name');
}
```

**Filters:**
```php
->filters([
    SelectFilter::make('status')->options([...]),
    Filter::make('created_at')
        ->form([
            DatePicker::make('created_from')->native(false),
            DatePicker::make('created_until')->native(false),
        ])
        ->query(function (Builder $query, array $data): Builder {
            return $query
                ->when($data['created_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                ->when($data['created_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
        }),
])
```

**Actions:**
```php
->actions([
    Tables\Actions\ActionGroup::make([
        Tables\Actions\EditAction::make(),
        Tables\Actions\DeleteAction::make(),
    ]),
])
```

### Pages Structure
```php
public static function getPages(): array
{
    return [
        'index' => Pages\ListYourModels::route('/'),
        'create' => Pages\CreateYourModel::route('/create'),
        'edit' => Pages\EditYourModel::route('/{record}/edit'),
        'view' => Pages\ViewYourModel::route('/{record}'),
    ];
}
```

### Relation Managers
```php
public static function getRelations(): array
{
    return [
        YourRelationManager::class,
    ];
}
```

### Status Constants
Located in `App\Constants\Status`:
- `Status::ACTIVE = 1`
- `Status::INACTIVE = 0`
- `Status::PHYSICAL_PRODUCT = 0`
- `Status::DIGITAL_PRODUCT = 1`

### Currency Input Pattern
```php
Forms\Components\TextInput::make('price')
    ->rules('nullable|numeric')
    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
```

## When to use me

- Creating new Filament Resources
- Adding/modifying forms, tables, or columns
- Creating RelationManagers
- Adding filters or actions
- Setting up navigation and pages
- Working with Spatie Media Library uploads

## Workflow

1. Check existing sibling resources for the correct pattern
2. Use `application-info` to verify Filament version
3. Use `database-schema` to understand model structure
4. Follow the tab-based form pattern when applicable
5. Always include SEO schema if the resource is public-facing
6. Use `currencyMask` for price fields
7. Use SpatieMediaLibraryFileUpload for images
8. Add navigation group and sort order consistent with existing resources

## Important Notes

- Do NOT use `Forms\Components\` prefix if already imported
- Heroicons use `heroicon-o-` (outline) and `heroicon-m-` (mini) prefixes
- Check `app/Filament/Resources/` for existing patterns before creating new ones
- This project uses Spatie Laravel Media Library for file uploads
- Follow existing permission patterns with `shouldCanUpdate()`
- Use `->disk(config('filesystems.upload_disk'))` for persistent upload storage
