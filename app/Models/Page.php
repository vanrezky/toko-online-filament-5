<?php

namespace App\Models;

use App\Enums\BlogPostStatus;
use App\Traits\HasMeta;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Page extends Model
{
    use HasFactory, HasMeta;

    public const REQUIRED_LEGAL_PAGE_SLUGS = [
        'syarat-ketentuan',
        'kebijakan-privasi',
    ];

    protected $fillable = ['title', 'content', 'image', 'is_status', 'slug', 'order', 'published_at', 'parent_id', 'show_in_menu', 'menu_location'];
    protected $casts = [
        'is_status' => BlogPostStatus::class,
        'show_in_menu' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::updating(function (Page $page): void {
            if (! $page->isRequiredLegalPage()) {
                return;
            }

            if ($page->isDirty('slug')) {
                throw ValidationException::withMessages([
                    'slug' => 'Slug halaman wajib tidak dapat diubah.',
                ]);
            }

            if ($page->isDirty('is_status') && $page->is_status !== BlogPostStatus::PUBLISHED) {
                throw ValidationException::withMessages([
                    'is_status' => 'Halaman wajib harus tetap dipublikasikan.',
                ]);
            }
        });

        static::deleting(function (Page $page): void {
            if ($page->isRequiredLegalPage()) {
                throw ValidationException::withMessages([
                    'page' => 'Halaman wajib tidak dapat dihapus.',
                ]);
            }
        });
    }

    public function isRequiredLegalPage(): bool
    {
        return in_array($this->getOriginal('slug') ?? $this->slug, self::REQUIRED_LEGAL_PAGE_SLUGS, true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_status', BlogPostStatus::PUBLISHED);
    }

    public function scopeHeaderMenu($query)
    {
        return $query->active()
            ->where('show_in_menu', true)
            ->whereIn('menu_location', ['header', 'both'])
            ->orderBy('order')
            ->orderBy('created_at');
    }

    public function scopeFooterMenu($query)
    {
        return $query->active()
            ->where('show_in_menu', true)
            ->whereIn('menu_location', ['footer', 'both'])
            ->orderBy('order')
            ->orderBy('created_at');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }
}
