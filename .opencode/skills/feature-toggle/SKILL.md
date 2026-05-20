---
name: feature-toggle
description: Hide or show application features by toggling all related files (Filament resources, routes, controllers, views, permissions). Use this when user wants to enable/disable a feature across the entire application.
license: MIT
compatibility: opencode
metadata:
  category: feature-management
  framework: laravel
---

## What I do

I hide or show application features by modifying ALL related files in the codebase. When a feature is hidden, it becomes completely inaccessible (admin panel, API, frontend, routes).

## Usage

Trigger this skill with: `/hide-feature <feature_name>` or `/show-feature <feature_name>`

Example: `/hide-feature reseller`

## Feature Registry

| Feature | Files to Toggle |
|---------|-----------------|
| reseller | app/Filament/Resources/ResellerResource.php, routes/web.php, app/Http/Controllers/Frontend/HomeController.php, resources/js/frontend/pages/Home/Index.vue, config/filament-shield.php |
| balance | app/Filament/Resources/BalanceResource.php |
| newsletter | app/Filament/Resources/NewsletterSubscriberResource.php, routes/web.php, resources/js/frontend/pages/Home/Index.vue, config/filament-shield.php |
| promotion | app/Filament/Resources/PromotionResource.php, app/Http/Middleware/HandleInertiaRequests.php, config/filament-shield.php |
| flashsale | app/Filament/Resources/FlashsaleResource.php, routes/web.php, app/Http/Controllers/Frontend/HomeController.php, config/filament-shield.php |

## Toggle Patterns

### Filament Resource (canAccess)

**To HIDE (disable):**
```php
static bool $shouldRegisterNavigation = false;

public static function canAccess(): bool
{
    return false;
}
```

**To SHOW (enable):**
```php
static bool $shouldRegisterNavigation = true;

public static function canAccess(): bool
{
    return parent::canAccess();
}
```

### routes/web.php

**To HIDE:** Comment out the route with `// @feature-toggle: feature_name`
```
// @feature-toggle: flashsale — uncomment to re-enable
// Route::middleware(['auth', 'verified'])->group(function () {
//     ...
// });
```

**To SHOW:** Uncomment the route block and remove the feature-toggle marker.

### Frontend Controller

**To HIDE:** Comment out code block with `// @feature-toggle: feature_name`

**To SHOW:** Uncomment and remove the marker.

### Frontend Vue Component

**To HIDE:** Add/change `v-if="false"` or comment out block with `<!-- @feature-toggle: feature_name -->`

**To SHOW:** Remove `v-if="false"` or uncomment the block.

### HandleInertiaRequests Middleware

**To HIDE:** Comment out sharing code with `// @feature-toggle: feature_name`

**To SHOW:** Uncomment and remove marker.

### Filament Shield (config/filament-shield.php)

**To HIDE:** Comment out the permission generator line with `// @feature-toggle:`

**To SHOW:** Uncomment and remove marker.

## Workflow

1. **Identify the feature** from user's input
2. **Find all related files** from the Feature Registry above
3. **For HIDE operation:**
   - Modify each file using the HIDE pattern
   - Ensure `canAccess()` returns `false`
   - Ensure routes are commented
   - Ensure UI components are hidden
4. **For SHOW operation:**
   - Modify each file using the SHOW pattern
   - Restore `canAccess()` to `parent::canAccess()`
   - Uncomment routes
   - Restore UI visibility
5. **Verify changes** by checking key files

## Important Notes

- ALWAYS use the exact patterns shown above
- Do NOT delete files, only disable/enable
- Keep the `@feature-toggle: feature_name` comment as marker for future toggling
- When hiding, make sure ALL entry points are disabled (routes, resources, UI)
- When showing, restore ALL entry points