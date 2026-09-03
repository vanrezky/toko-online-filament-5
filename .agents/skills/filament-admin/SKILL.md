---
name: filament-admin
description: Work with the existing Filament admin panel of this Toko Online project. Use this when modifying existing admin resources, understanding the admin structure, or adding features to the admin dashboard.
license: MIT
compatibility: opencode
metadata:
  category: admin-panel
  framework: filament
  project: toko-online
---

## What I do

I help navigate and modify the existing Filament admin panel for this e-commerce project.

## Admin Panel Structure

### Navigation Groups & Resources

**Product Group:**
| Resource | Slug | Icon | Description |
|----------|------|------|-------------|
| ProductResource | products | heroicon-o-shopping-bag | Products with variants, pricing, stock |
| CategoryResource | categories | heroicon-o-list-bullet | Product categories with SEO |
| WarehouseResource | (default) | heroicon-o-home-modern | Shipping warehouse locations |

**Transaction Group:**
| Resource | Slug | Icon | Description |
|----------|------|------|-------------|
| TransactionResource | (default) | heroicon-o-banknotes | Orders with status management |

**Customer Group:**
| Resource | Slug | Icon | Description |
|----------|------|------|-------------|
| CustomerResource | (default) | heroicon-o-users | Customer accounts |
| ResellerResource | reseller-level | heroicon-o-tag | Reseller levels & pricing |
| BalanceResource | balances | heroicon-o-rectangle-stack | Customer balances |

**Promo Group:**
| Resource | Slug | Icon | Description |
|----------|------|------|-------------|
| FlashsaleResource | (default) | heroicon-o-bolt | Flash sales |
| PromotionResource | (default) | heroicon-o-gift | Promotions |
| VoucherResource | vouchers | heroicon-o-scissors | Discount vouchers |
| SliderResource | (default) | heroicon-o-photo | Homepage sliders |

**Blog Group:**
| Resource | Slug | Icon | Description |
|----------|------|------|-------------|
| BlogPostResource | blog/posts | heroicon-o-document-text | Blog posts |
| BlogCategoryResource | (default) | heroicon-o-folder | Blog categories |
| PageResource | (default) | heroicon-o-document-text | Static pages (CMS) |

**Master Group:**
| Resource | Slug | Icon | Description |
|----------|------|------|-------------|
| ProvinceResource | (default) | heroicon-o-map | Provinces |
| DistrictResource | (default) | heroicon-o-building-office | Districts |
| SubDistrictResource | (default) | heroicon-o-building-storefront | Sub-districts |
| VillageResource | (default) | heroicon-o-rectangle-stack | Villages |
| CountryResource | (default) | heroicon-o-globe | Countries |
| FaqResource | faqs | heroicon-o-sparkles | FAQs |

**Setting Group:**
| Resource | Slug | Icon | Description |
|----------|------|------|-------------|
| UserResource | setting/users | heroicon-o-users | Admin users |
| EmailTemplateResource | (default) | heroicon-o-envelope | Email templates |

**Appearance Group:**
| Resource | Slug | Icon | Description |
|----------|------|------|-------------|
| TemplateResource | templates | heroicon-o-paint-brush | Page templates |
| TemplateSectionResource | (default) | heroicon-o-squares-plus | Template sections |

**Logs Group:**
| Resource | Slug | Icon | Description |
|----------|------|------|-------------|
| EmailLogResource | (default) | heroicon-o-envelope | Sent email logs |
| ExceptionResource | (default) | heroicon-o-bug-ant | Exception logs (via filament-exceptions) |

### Key Features of Each Resource

**ProductResource:**
- Tabs: Code & Category, Name & Description, Price, Inventory, Images, FAQs, SEO
- Product types: Physical vs Digital (toggle shows/hides weight/warehouse)
- Reseller pricing with wholesale tiers
- Spatie media library for product images (max 5)
- Product variants with attributes
- Stock management with security stock alerts

**TransactionResource:**
- Statuses: unpaid, shipped, delivered, rejected, completed
- COD (Cash on Delivery) support
- Customer relation with eager loading
- Date range filters
- View page for order details

**CategoryResource:**
- Image upload with 1:1 ratio
- Product count column
- Cannot delete if has products
- Toggle for active/featured status

### Schema Helpers

**TitleSchema** (`App\Filament\Resources\Schema\TitleSchema`):
- `TitleSchema::title('name')` — Auto-generates slug and meta title
- `TitleSchema::slug()` — Slug with alpha_dash validation
- `TitleSchema::hidden()` — Hidden field for manual slug flag

**MetaSchema** (`App\Filament\Resources\Schema\MetaSchema`):
- `MetaSchema::get()` — Returns group with meta title, description, keywords
- Character counters and live updates
- Tracks manual changes vs auto-generated

### Spatie Media Library
This project uses Spatie Laravel Media Library:
- File upload: `SpatieMediaLibraryFileUpload::make('images')`
- Image column: `SpatieMediaLibraryImageColumn::make('images')->conversion('thumb')`
- Storage disk: `config('filesystems.upload_disk')`
- Upload paths in `App\Constants\UploadPath`

### Payment Integration
- PaymentGatewayResource manages payment methods
- Midtrans integration via `midtrans/midtrans-php`
- Dynamic payment gateway support
- Webhook handling with signature verification

### Settings
- Uses Spatie Laravel Settings (`app/Settings/`)
- General settings via GeneralSettingResource
- Settings migrations in `database/settings/`

## When to use me

- Modifying existing admin resources
- Adding new fields to forms or tables
- Understanding admin navigation structure
- Working with product/transaction/order data
- Managing payment gateways
- Working with email templates
- Modifying page templates (CMS)
- Understanding permission/role structure (via filament-shield)

## Workflow

1. Use `list-routes` to find admin panel routes (usually under `/admin/*`)
2. Use `database-schema` to understand related tables
3. Check existing resource in `app/Filament/Resources/` before modifying
4. Use `application-info` to check installed packages
5. Follow existing tab patterns when adding new fields
6. Maintain navigation groups consistency
7. Use schema helpers for SEO fields when applicable

## Important Notes

- Admin panel is at `/admin` (default Filament path)
- Uses `filament-shield` for roles & permissions
- Uses `filament-spatie-laravel-media-library-plugin` for uploads
- Uses `filament-spatie-laravel-settings-plugin` for settings
- Uses `filament-spatie-laravel-tags-plugin` for tags
- Currency format: IDR with `.` thousand separator and `,` decimal separator
- This project has 50+ Eloquent models
- Follow existing Service pattern in `app/Services/` for business logic
- Never hardcode payment keys — use `config/midtrans.php` + `.env`
