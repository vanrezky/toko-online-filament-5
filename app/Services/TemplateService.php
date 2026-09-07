<?php

namespace App\Services;

use App\Models\Template;
use App\Models\TemplateSection;

class TemplateService
{
    protected const CACHE_PREFIX = 'template_';

    protected const CACHE_TTL = 3600; // 1 hour

    protected const ACTIVE_CACHE_KEY = self::CACHE_PREFIX.'active';

    public function getActiveTemplate(): ?Template
    {
        return CacheService::rememberManaged(
            'template',
            self::ACTIVE_CACHE_KEY,
            self::CACHE_TTL,
            fn () => Template::active()
                ->select(['id', 'uuid', 'name', 'color_scheme'])
                ->with([
                    'sections' => fn ($query) => $query
                        ->where('is_active', true)
                        ->select(['id', 'uuid', 'template_id', 'name', 'type', 'description', 'icon', 'is_active', 'order_priority'])
                        ->orderBy('order_priority'),
                    'sections.contents:id,section_id,field_id,value',
                    'sections.contents.field:id,key',
                ])
                ->first(),
        );
    }

    public function getActiveTemplateWithSections(): ?Template
    {
        return $this->getActiveTemplate();
    }

    public function getPreviewTemplate(Template $template): Template
    {
        return $template->load([
            'sections' => fn ($query) => $query
                ->select(['id', 'uuid', 'template_id', 'name', 'type', 'description', 'icon', 'is_active', 'order_priority'])
                ->orderBy('order_priority'),
            'sections.contents:id,section_id,field_id,value',
            'sections.contents.field:id,key',
        ]);
    }

    public function getSectionContent(TemplateSection $section, string $key, mixed $default = null): mixed
    {
        $content = $section->contents
            ->filter(fn ($c) => $c->field?->key === $key)
            ->first();

        return $content?->value ?? $default;
    }

    public function getSectionContents(TemplateSection $section): array
    {
        return $section->contents
            ->mapWithKeys(fn ($c) => [$c->field?->key ?? 'unknown' => $c->value])
            ->toArray();
    }

    public function formatSections(Template $template): array
    {
        return $template->sections
            ->filter(fn ($s) => $s->is_active)
            ->map(function ($section) {
                return [
                    'id' => $section->id,
                    'uuid' => $section->uuid,
                    'name' => $section->name,
                    'type' => $section->type,
                    'description' => $section->description,
                    'icon' => $section->icon,
                    'contents' => $this->getSectionContents($section),
                ];
            })
            ->values()
            ->toArray();
    }

    public function getSectionByType(string $type): ?array
    {
        $template = $this->getActiveTemplate();

        if (! $template) {
            return null;
        }

        return collect($this->formatSections($template))
            ->first(fn ($s) => $s['type'] === $type);
    }

    public function getColorScheme(): array
    {
        $template = CacheService::getManaged('template', self::ACTIVE_CACHE_KEY);

        if ($template instanceof Template) {
            return $this->normalizeColorScheme($template->color_scheme);
        }

        return CacheService::rememberManaged(
            'template',
            self::CACHE_PREFIX.'colors',
            self::CACHE_TTL,
            function () {
                $template = Template::active()->first();

                return $this->normalizeColorScheme($template?->color_scheme);
            }
        );
    }

    /**
     * Normalize the five admin-managed colors into the storefront contract.
     *
     * The admin form stores the text color under `text`, while the storefront
     * consumes it as `foreground`. Keep destructive as a stable runtime
     * fallback because it is not part of the admin color palette.
     */
    public function normalizeColorScheme(?array $colors): array
    {
        $defaults = $this->getDefaultColorScheme();

        return [
            'primary' => $colors['primary'] ?? $defaults['primary'],
            'secondary' => $colors['secondary'] ?? $defaults['secondary'],
            'accent' => $colors['accent'] ?? $defaults['accent'],
            'background' => $colors['background'] ?? $defaults['background'],
            'foreground' => $colors['foreground'] ?? $colors['text'] ?? $defaults['foreground'],
            'destructive' => $colors['destructive'] ?? $defaults['destructive'],
        ];
    }

    public function getDefaultColorScheme(): array
    {
        return [
            'primary' => '#F97316',
            'secondary' => '#F5F3FC',
            'accent' => '#FB923C',
            'destructive' => '#F43F5E',
            'background' => '#FCFCFE',
            'foreground' => '#2D1B0E',
        ];
    }

    public function clearCache(): void
    {
        CacheService::forgetManaged('template', self::CACHE_PREFIX.'active');
        CacheService::forgetManaged('template', self::CACHE_PREFIX.'colors');
    }

    public function warmCache(): void
    {
        $this->clearCache();
        $this->getActiveTemplate();
        $this->getColorScheme();
    }
}
