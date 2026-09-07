<?php

use App\Services\CacheService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Add the enhanced storefront keys to existing template sections.
     *
     * The operation is intentionally additive: existing administrator values
     * are never overwritten and templates without a matching section are
     * left unchanged.
     */
    public function up(): void
    {
        $definitions = [
            'hero' => [
                ['key' => 'eyebrow', 'label' => 'Label Atas', 'type' => 'text', 'default_value' => 'PILIHAN TERBAIK UNTUK HARI INI', 'order_priority' => 7],
                ['key' => 'badge', 'label' => 'Badge Promo', 'type' => 'text', 'default_value' => 'PROMO', 'order_priority' => 8],
                ['key' => 'secondary_text', 'label' => 'Teks Tombol Sekunder', 'type' => 'text', 'default_value' => 'Lihat Promo', 'order_priority' => 9],
                ['key' => 'secondary_link', 'label' => 'Link Tombol Sekunder', 'type' => 'url', 'default_value' => '/vouchers', 'order_priority' => 10],
                ['key' => 'promo_label', 'label' => 'Label Nilai Promo', 'type' => 'text', 'default_value' => 'HEMAT', 'order_priority' => 11],
                ['key' => 'promo_value', 'label' => 'Nilai Promo', 'type' => 'text', 'default_value' => '70%', 'order_priority' => 12],
                ['key' => 'trust_points', 'label' => 'Poin Kepercayaan', 'type' => 'textarea', 'default_value' => json_encode([
                    ['title' => 'Pengiriman Cepat', 'description' => 'Pesanan diproses dengan cepat.'],
                    ['title' => 'Produk Original', 'description' => 'Kualitas produk terjamin.'],
                    ['title' => 'Dukungan Pelanggan', 'description' => 'Kami siap membantu Anda.'],
                ], JSON_UNESCAPED_UNICODE), 'order_priority' => 13],
            ],
        ];

        DB::table('template_sections')->orderBy('id')->get()->each(function (object $section) use ($definitions): void {
            foreach ($definitions[$section->type] ?? [] as $definition) {
                $fieldId = DB::table('template_section_fields')
                    ->where('section_id', $section->id)
                    ->where('key', $definition['key'])
                    ->value('id');

                if (! $fieldId) {
                    $fieldId = DB::table('template_section_fields')->insertGetId([
                        'uuid' => (string) Str::uuid(),
                        'section_id' => $section->id,
                        'key' => $definition['key'],
                        'label' => $definition['label'],
                        'type' => $definition['type'],
                        'placeholder' => $definition['default_value'],
                        'default_value' => $definition['default_value'],
                        'options' => null,
                        'is_required' => false,
                        'order_priority' => $definition['order_priority'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $contentExists = DB::table('template_section_contents')
                    ->where('section_id', $section->id)
                    ->where('field_id', $fieldId)
                    ->exists();

                if (! $contentExists) {
                    DB::table('template_section_contents')->insert([
                        'uuid' => (string) Str::uuid(),
                        'section_id' => $section->id,
                        'field_id' => $fieldId,
                        'value' => $definition['default_value'],
                        'meta' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        CacheService::forgetManaged('template', 'template_active');
        CacheService::forgetManaged('template', 'template_colors');
    }

    public function down(): void
    {
        // The migration is additive and intentionally preserves administrator
        // values on rollback; removing content would be data-destructive.
    }
};
