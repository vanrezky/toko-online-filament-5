<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\TemplateSection;
use App\Models\User;
use App\Services\TemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TemplateDrivenStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_section_types_expose_slider_carousel(): void
    {
        $this->assertSame('Slider / Carousel', TemplateSection::types()[TemplateSection::TYPE_HERO_CAROUSEL]);
    }

    public function test_template_section_types_expose_vouchers(): void
    {
        $this->assertSame('Voucher', TemplateSection::types()[TemplateSection::TYPE_VOUCHERS]);
    }

    public function test_template_service_maps_admin_text_color_to_foreground(): void
    {
        $colors = app(TemplateService::class)->normalizeColorScheme([
            'primary' => '#112233',
            'secondary' => '#223344',
            'accent' => '#334455',
            'background' => '#FDFDFD',
            'text' => '#101010',
        ]);

        $this->assertSame('#101010', $colors['foreground']);
        $this->assertSame('#F43F5E', $colors['destructive']);
    }

    public function test_template_service_uses_safe_defaults_for_missing_colors(): void
    {
        $colors = app(TemplateService::class)->normalizeColorScheme(null);

        $this->assertSame('#F97316', $colors['primary']);
        $this->assertSame('#F5F3FC', $colors['secondary']);
        $this->assertSame('#FB923C', $colors['accent']);
        $this->assertSame('#FCFCFE', $colors['background']);
        $this->assertSame('#2D1B0E', $colors['foreground']);
    }

    public function test_homepage_exposes_active_template_section_order(): void
    {
        $template = Template::query()->create([
            'name' => 'Dynamic template',
            'code' => 'dynamic-template',
            'color_scheme' => ['primary' => '#112233', 'text' => '#101010'],
            'is_active' => true,
        ]);

        TemplateSection::query()->create([
            'template_id' => $template->id,
            'name' => 'Products',
            'type' => TemplateSection::TYPE_PRODUCTS_GRID,
            'is_active' => true,
            'order_priority' => 1,
        ]);

        TemplateSection::query()->create([
            'template_id' => $template->id,
            'name' => 'Hidden newsletter',
            'type' => TemplateSection::TYPE_NEWSLETTER,
            'is_active' => false,
            'order_priority' => 2,
        ]);
        TemplateSection::query()->create([
            'template_id' => $template->id,
            'name' => 'Hero',
            'type' => TemplateSection::TYPE_HERO,
            'is_active' => true,
            'order_priority' => 3,
        ]);

        $this->get(route('frontend.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home/Index')
                ->where('template.sections.0.type', TemplateSection::TYPE_PRODUCTS_GRID)
                ->where('template.sections.1.type', TemplateSection::TYPE_HERO)
                ->missing('template.sections.2')
                ->where('colorScheme.foreground', '#101010')
            );
    }

    public function test_template_preview_requires_view_authorization_and_isolated_preview_state(): void
    {
        $template = Template::query()->create([
            'name' => 'Preview template',
            'code' => 'preview-template',
            'color_scheme' => ['primary' => '#112233'],
            'is_active' => false,
        ]);

        $template->sections()->create([
            'name' => 'Preview hero',
            'type' => TemplateSection::TYPE_HERO,
            'is_active' => false,
            'order_priority' => 1,
        ]);

        $this->get(route('frontend.template-preview', ['template' => $template->uuid]))
            ->assertRedirect(route('frontend.login'));

        $admin = User::factory()->create(['is_super_user' => true]);

        $this->actingAs($admin)
            ->get(route('frontend.template-preview', ['template' => $template->uuid]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home/Index')
                ->where('templatePreview', true)
                ->where('template.sections.0.is_active', false)
            );
    }
}
