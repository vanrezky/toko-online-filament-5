<?php

namespace Tests\Unit\Services;

use App\Services\DashboardStats;
use ReflectionMethod;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    public function test_dashboard_cache_key_is_unique_for_category_and_transaction_status_filters(): void
    {
        $method = new ReflectionMethod(DashboardStats::class, 'getCacheKey');
        $method->setAccessible(true);

        $categoryStats = new DashboardStats([
            'startDate' => '2026-09-01',
            'endDate' => '2026-09-02',
            'categoryId' => 7,
            'transactionStatus' => 'completed',
        ]);
        $otherStats = new DashboardStats([
            'startDate' => '2026-09-01',
            'endDate' => '2026-09-02',
            'categoryId' => 8,
            'transactionStatus' => 'cancelled',
        ]);

        $categoryKey = $method->invoke($categoryStats, 'trend');
        $otherKey = $method->invoke($otherStats, 'trend');

        $this->assertNotSame($categoryKey, $otherKey);
        $this->assertStringContainsString('category_7_status_completed', $categoryKey);
        $this->assertStringContainsString('category_8_status_cancelled', $otherKey);
    }
}
