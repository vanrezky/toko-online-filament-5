<?php

use App\Models\Template;
use App\Models\TemplateSection;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Add the slider/carousel section to existing templates without
     * changing any section content or administrator ordering decisions.
     */
    public function up(): void
    {
        Template::query()->each(function (Template $template): void {
            if ($template->sections()->where('type', TemplateSection::TYPE_HERO_CAROUSEL)->exists()) {
                return;
            }

            $nextOrder = ((int) $template->sections()->max('order_priority')) + 1;

            TemplateSection::query()->create([
                'template_id' => $template->id,
                'name' => 'Slider / Carousel',
                'type' => TemplateSection::TYPE_HERO_CAROUSEL,
                'description' => 'Carousel promosi yang menggunakan data slider aktif dari katalog promosi.',
                'icon' => 'heroicon-o-photo',
                'is_active' => true,
                'order_priority' => $nextOrder,
            ]);
        });
    }

    public function down(): void
    {
        // Keep administrator-created or reordered carousel sections intact on rollback.
    }
};
