<?php

namespace Tests\Feature\Platform\Audit;

use App\Models\Product;
use App\Models\User;
use App\Modules\Platform\Audit\Services\AuditLogService;
use App\Modules\Platform\Support\Correlation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_critical_product_update_records_allow_listed_old_and_new_values_with_actor(): void
    {
        $actor = User::factory()->create();
        $product = Product::factory()->create(['price' => 120000, 'stock' => 10]);

        $this->actingAs($actor);
        $product->update(['price' => 135000, 'stock' => 8]);

        $activity = Activity::query()->latest('id')->firstOrFail();

        $this->assertSame('updated', $activity->event);
        $this->assertTrue($activity->causer->is($actor));
        $this->assertSame(120000, (int) $activity->properties->get('old')['price']);
        $this->assertSame(135000, (int) $activity->properties->get('attributes')['price']);
        $this->assertSame(10, (int) $activity->properties->get('old')['stock']);
        $this->assertSame(8, (int) $activity->properties->get('attributes')['stock']);
    }

    public function test_non_allow_listed_product_content_does_not_create_a_noisy_activity(): void
    {
        $product = Product::factory()->create();
        $initialCount = Activity::query()->count();

        $product->update(['description' => 'Updated public product description']);

        $this->assertSame($initialCount, Activity::query()->count());
    }

    public function test_system_generated_activity_has_no_causer_or_request_context(): void
    {
        $product = Product::factory()->create();

        $activity = Activity::query()->where('subject_id', $product->id)->latest('id')->firstOrFail();

        $this->assertNull($activity->causer);
        $this->assertArrayNotHasKey('context', $activity->properties->all());
    }

    public function test_user_sensitive_attributes_are_not_automatically_audited(): void
    {
        User::factory()->create(['password' => 'secret-value']);

        $this->assertSame(0, Activity::query()->count());
    }

    public function test_explicit_business_activity_can_replace_an_automatic_model_activity_without_duplication(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $initialCount = Activity::query()->count();

        $product->disableLogging()->update(['stock' => 8]);
        app(AuditLogService::class)->logBusinessAction(
            'product stock corrected',
            $product,
            ['stock' => 10],
            ['stock' => 8],
        );

        $activities = Activity::query()->latest('id')->take(2)->get();

        $this->assertSame($initialCount + 1, Activity::query()->count());
        $this->assertSame('business', $activities->first()->event);
        $this->assertSame('product stock corrected', $activities->first()->description);
    }

    public function test_console_execution_business_activity_records_the_active_correlation_id(): void
    {
        $correlationId = (string) Str::uuid();
        Correlation::set($correlationId);
        $product = Product::factory()->create();

        app(AuditLogService::class)->logBusinessAction(
            'order expired and cancelled',
            $product,
            ['status' => 'packed'],
            ['status' => 'cancelled'],
        );

        $activity = Activity::query()->latest('id')->firstOrFail();

        $this->assertSame($correlationId, $activity->properties->get('context')['correlation_id'] ?? null);
    }
}
