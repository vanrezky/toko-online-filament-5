<?php

use App\Models\Template;
use App\Models\TemplateSection;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Add the voucher section to existing templates without changing
     * existing section content or administrator ordering decisions.
     */
    public function up(): void
    {
        Template::query()->each(function (Template $template): void {
            if ($template->sections()->where('type', TemplateSection::TYPE_VOUCHERS)->exists()) {
                return;
            }

            $nextOrder = ((int) $template->sections()->max('order_priority')) + 1;

            TemplateSection::query()->create([
                'template_id' => $template->id,
                'name' => 'Voucher',
                'type' => TemplateSection::TYPE_VOUCHERS,
                'description' => 'Daftar voucher aktif yang dapat digunakan pelanggan saat checkout.',
                'icon' => 'heroicon-o-ticket',
                'is_active' => true,
                'order_priority' => $nextOrder,
            ]);
        });
    }

    public function down(): void
    {
        // Keep administrator-created or reordered voucher sections intact on rollback.
    }
};
