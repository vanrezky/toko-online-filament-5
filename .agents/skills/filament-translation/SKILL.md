---
name: filament-translation
description: Create Laravel translation files for Filament admin resources. Use this when adding or updating Indonesian (id) translations for Filament Resource classes, Pages, and Schema helpers.
license: MIT
compatibility: opencode
metadata:
  category: admin-panel
  framework: filament
  project: toko-online
---

## What I do

I help create and apply keyed Laravel translations for Filament admin resources following this project's established conventions.

## Translation Key Convention

All Filament resource translations use the `admin/{resource-name}` namespace pattern in `__()` calls.

**Format:** `__('admin/{resource-name}.{section}.{key}')`

**Translation file location:** `lang/id/admin/{resource-name}.php`

**Example for CategoryResource:**
- Translation key: `__('admin/category-resource.fields.name')`
- Translation file: `lang/id/admin/category-resource.php`
- File key path: `fields.name`

## Translation File Structure

Every resource translation file must follow this exact structure:

```php
<?php

return [

    // Navigation & model labels (used in Resource class overrides)
    'navigation_label' => 'Label Navigasi',
    'navigation_group' => 'Grup Navigasi',
    'model_label' => 'Singular',
    'plural_model_label' => 'Plural',

    // Page titles (used in Page class getTitle() methods)
    'pages' => [
        'list' => [
            'title' => 'Judul Halaman List',
        ],
        'create' => [
            'title' => 'Buat Item',
        ],
        'edit' => [
            'title' => 'Edit Item',
        ],
    ],

    // Form tab labels
    'tabs' => [
        'main' => 'Utama',
        'seo' => 'SEO',
    ],

    // Form field labels, placeholders, helper texts
    'fields' => [
        'name' => 'Nama',
        'image' => 'Gambar',
        'is_active' => 'Aktif',
        'image_helper' => 'Rasio 1:1. Ukuran maksimal 1MB',
    ],

    // Table column labels
    'columns' => [
        'name' => 'Nama',
        'is_active' => 'Aktif',
    ],

    // Notification messages (used in afterStateUpdated, delete actions, etc.)
    'notifications' => [
        'cannot_delete' => 'Item tidak dapat dihapus',
        'cannot_delete_bulk' => 'Ada item yang tidak dapat dihapus',
        'activation_updated' => 'Status aktif berhasil diperbarui',
    ],

];
```

## Resource Class Changes

When adding translations to a Filament Resource, make these changes:

### 1. Remove static property declarations, add method overrides

Replace:
```php
protected static ?string $navigationLabel = 'Hardcoded Label';
protected static ?string $navigationGroup = 'Hardcoded Group';
```

With method overrides:
```php
public static function getNavigationLabel(): string
{
    return __('admin/{resource-name}.navigation_label');
}

public static function getNavigationGroup(): ?string
{
    return __('admin/{resource-name}.navigation_group');
}

public static function getModelLabel(): string
{
    return __('admin/{resource-name}.model_label');
}

public static function getPluralModelLabel(): string
{
    return __('admin/{resource-name}.plural_model_label');
}
```

### 2. Replace all hardcoded strings in form(), table(), and helper methods

**Tab labels:**
```php
// Before:
Tabs\Tab::make('Main')
Tabs\Tab::make('SEO')

// After:
Tabs\Tab::make(__('admin/{resource-name}.tabs.main'))
Tabs\Tab::make(__('admin/{resource-name}.tabs.seo'))
```

**Field labels & helper texts:**
```php
// Before:
->label(__('Category Name'))
->helperText(__('Ratio Is 1:1. Maximum size is 1MB'))

// After:
->label(__('admin/{resource-name}.fields.name'))
->helperText(__('admin/{resource-name}.fields.image_helper'))
```

**Column labels:**
```php
// Before:
->label(__('Category Name'))

// After:
->label(__('admin/{resource-name}.columns.name'))
```

**Column prefixes:**
```php
// Before:
->prefix('Products: ')

// After:
->prefix(__('admin/{resource-name}.columns.products_prefix'))
```

**Notification messages:**
```php
// Before:
return notification(__('Category cannot be deleted'), 'warning');

// After:
return notification(__('admin/{resource-name}.notifications.cannot_delete'), 'warning');
```

### 3. ToggleColumn / IconColumn patterns

```php
public static function getIsActiveColumn()
{
    if (self::shouldCanUpdate()) {
        return Tables\Columns\ToggleColumn::make('is_active')
            ->afterStateUpdated(fn() => notification(__('admin/{resource-name}.notifications.activation_updated')))
            ->label(__('admin/{resource-name}.columns.is_active'));
    }
    return Tables\Columns\IconColumn::make('is_active')->boolean()->label(__('admin/{resource-name}.columns.is_active'));
}
```

## Page Class Changes

### ListRecords page
```php
class List{Resources} extends ListRecords
{
    protected static string $resource = {Resource}::class;

    public function getTitle(): string  // NOT static!
    {
        return __('admin/{resource-name}.pages.list.title');
    }
}
```

### CreateRecord page
```php
class Create{Resource} extends CreateRecord
{
    protected static string $resource = {Resource}::class;

    public function getTitle(): string  // NOT static!
    {
        return __('admin/{resource-name}.pages.create.title');
    }
}
```

### EditRecord page
```php
class Edit{Resource} extends EditRecord
{
    protected static string $resource = {Resource}::class;

    public function getTitle(): string  // NOT static!
    {
        return __('admin/{resource-name}.pages.edit.title');
    }
}
```

**IMPORTANT:** `getTitle()` and `getBreadcrumb()` are instance methods (NOT static) on Filament page classes. Do NOT make them `public static function`. Breadcrumb is auto-generated by Filament from `getModelLabel()` / `getPluralModelLabel()` — do NOT override `getBreadcrumb()` unless there's a specific need.

## Schema Helpers (TitleSchema & MetaSchema)

When a resource uses `TitleSchema` or `MetaSchema`, those schemas have their own translatable strings. Check if a `meta-schema.php` translation file exists. If not, create it at `lang/id/admin/meta-schema.php`:

```php
<?php

return [
    'title' => 'Judul Meta',
    'description' => 'Deskripsi Meta',
    'keywords' => 'Kata Kunci Meta',
    'write_excerpt' => 'Tulis ringkasan untuk posting Anda',
    'separate_keywords' => 'Pisahkan kata kunci dengan koma',
];
```

Then update `MetaSchema.php` to reference keyed translations:
```php
TextInput::make('title')->label(__('admin/meta-schema.title'))
Textarea::make('description')->label(__('admin/meta-schema.description'))
    ->hint(__('admin/meta-schema.write_excerpt'))
TextInput::make('keyword')->label(__('admin/meta-schema.keywords'))
    ->hint(__('admin/meta-schema.separate_keywords'))
```

## Indonesian (id) Translation Guidelines

When writing Indonesian translations:

| English | Indonesian | Notes |
|---------|-----------|-------|
| Active | Aktif | |
| Featured | Unggulan | |
| Image | Gambar | |
| Name | Nama | |
| Create | Buat | |
| Edit | Edit | Keep as-is, commonly used |
| Delete | Hapus | |
| Save | Simpan | |
| Cancel | Batal | |
| Main | Utama | For tab labels |
| SEO | SEO | Keep as-is |
| Status | Status | Same word |
| Product | Produk | |
| Category | Kategori | |
| Total | Total | Same word |
| Cannot | Tidak dapat | |
| Updated successfully | Berhasil diperbarui | |
| Cannot be deleted | Tidak dapat dihapus | |
| Maximum size | Ukuran maksimal | |
| Ratio | Rasio | |

## Workflow

1. **Read the Resource file** — Identify all hardcoded English strings and `__('English string')` calls
2. **Read related Page files** — Check List, Create, Edit pages for title overrides
3. **Read Schema files** — Check if MetaSchema or TitleSchema are used and need translation
4. **Check existing translation files** — Look at `lang/id/admin/` for existing files to avoid duplication
5. **Create/update the translation file** — Follow the structure above
6. **Update the Resource class** — Replace property declarations with method overrides, replace all `__()` calls
7. **Update Page classes** — Add `getTitle()` instance method overrides
8. **Update Schema classes** — Replace `__()` calls if applicable
9. **Verify** — Run `php artisan tinker` to confirm all `__('admin/{resource-name}.key')` resolve correctly

## Common Mistakes to Avoid

1. **Do NOT use static `getTitle()`** on Page classes — Filament page `getTitle()` is an instance method
2. **Do NOT override `getBreadcrumb()`** — Filament generates breadcrumbs from model labels automatically
3. **Do NOT use `'string'` as `__()` key** — Always use dot-notation keys like `__('admin/category-resource.fields.name')`
4. **Do NOT hardcode `$navigationLabel` and `$navigationGroup`** — Override with `getNavigationLabel()` and `getNavigationGroup()` methods
5. **Do NOT forget `model_label` and `plural_model_label`** — These control Filament's auto-generated labels
6. **Do NOT create `lang/en/` files** — Only create `lang/id/` translations. English falls back to the key itself.
7. **Keep translation keys in English** — Keys like `fields.is_active`, `columns.name`, `notifications.cannot_delete` are in English. Only the VALUES are in Indonesian.

## Existing Translation Reference

| Resource | File | Status |
|----------|------|--------|
| CategoryResource | `lang/id/admin/category-resource.php` | Done |
| MetaSchema | - | Not yet translated |
| TitleSchema | - | Not yet translated |

When translating other resources (ProductResource, BlogCategoryResource, etc.), follow the exact same pattern using `admin/{resource-slug}` as the namespace.