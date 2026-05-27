<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStats
{
    protected array $filters;
    protected int $cacheSeconds = 300;
    protected ?Carbon $startDate = null;
    protected ?Carbon $endDate = null;
    protected ?array $previousPeriod = null;
    protected ?array $combinedStats = null;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function getStartDate(): ?Carbon
    {
        if ($this->startDate) {
            return $this->startDate;
        }

        $this->startDate = isset($this->filters['startDate'])
            ? Carbon::parse($this->filters['startDate'])->startOfDay()
            : now()->subDays(6)->startOfDay();

        return $this->startDate;
    }

    public function getEndDate(): ?Carbon
    {
        if ($this->endDate) {
            return $this->endDate;
        }

        $this->endDate = isset($this->filters['endDate'])
            ? Carbon::parse($this->filters['endDate'])->endOfDay()
            : now()->endOfDay();

        return $this->endDate;
    }

    protected function getCacheKey(string $method): string
    {
        $start = $this->getStartDate()?->format('Y-m-d') ?? 'default';
        $end = $this->getEndDate()?->format('Y-m-d') ?? 'default';
        return "dashboard_stats_{$method}_{$start}_{$end}";
    }

    public function getRevenueStats(): array
    {
        return Cache::remember($this->getCacheKey('revenue'), $this->cacheSeconds, function () {
            $stats = $this->getCombinedStats();
            return [
                'value' => $stats['current_revenue'],
                'previous' => $stats['previous_revenue'],
                'percent_change' => $this->calculatePercentChange($stats['current_revenue'], $stats['previous_revenue']),
            ];
        });
    }

    public function getOrdersStats(): array
    {
        return Cache::remember($this->getCacheKey('orders'), $this->cacheSeconds, function () {
            $stats = $this->getCombinedStats();
            return [
                'value' => $stats['current_orders'],
                'previous' => $stats['previous_orders'],
                'percent_change' => $this->calculatePercentChange($stats['current_orders'], $stats['previous_orders']),
            ];
        });
    }

    public function getNewCustomersStats(): array
    {
        return Cache::remember($this->getCacheKey('customers'), $this->cacheSeconds, function () {
            $current = Customer::whereBetween('created_at', [$this->getStartDate(), $this->getEndDate()])->count();
            $prevPeriod = $this->getPreviousPeriod();
            $previous = Customer::whereBetween('created_at', [$prevPeriod['start'], $prevPeriod['end']])->count();
            
            return [
                'value' => $current,
                'previous' => $previous,
                'percent_change' => $this->calculatePercentChange($current, $previous),
            ];
        });
    }

    public function getAverageOrderValueStats(): array
    {
        return Cache::remember($this->getCacheKey('aov'), $this->cacheSeconds, function () {
            $stats = $this->getCombinedStats();
            $currentAOV = $stats['current_orders'] > 0 ? $stats['current_revenue'] / $stats['current_orders'] : 0;
            $previousAOV = $stats['previous_orders'] > 0 ? $stats['previous_revenue'] / $stats['previous_orders'] : 0;

            return [
                'value' => $currentAOV,
                'previous' => $previousAOV,
                'percent_change' => $this->calculatePercentChange($currentAOV, $previousAOV),
            ];
        });
    }

    public function getSalesTrend(): array
    {
        return Cache::remember($this->getCacheKey('trend'), $this->cacheSeconds, function () {
            $startDate = $this->getStartDate() ?? now()->subDays(6)->startOfDay();
            $endDate = $this->getEndDate() ?? now();
            $days = $startDate->diffInDays($endDate) + 1;

            $currentPeriod = $this->getDailySales($startDate, $endDate);
            
            $prevStart = $startDate->copy()->subDays($days);
            $prevEnd = $startDate->copy()->subDay();
            $previousPeriod = $this->getDailySales($prevStart, $prevEnd);

            return [
                'labels' => $currentPeriod['labels'],
                'current' => $currentPeriod['values'],
                'previous' => $previousPeriod['values'],
            ];
        });
    }

    public function getOrdersByStatus(): array
    {
        return Cache::remember($this->getCacheKey('status'), $this->cacheSeconds, function () {
            $results = Transaction::query()
                ->when($this->getStartDate() && $this->getEndDate(), fn($q) => $q->whereBetween('created_at', [$this->getStartDate(), $this->getEndDate()]))
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $result = [];
            foreach (TransactionStatus::cases() as $status) {
                $result[$status->value] = $results[$status->value] ?? 0;
            }

            return $result;
        });
    }

    public function getTopProducts(int $limit = 5): array
    {
        return Cache::remember($this->getCacheKey("top_{$limit}"), $this->cacheSeconds, function () use ($limit) {
            return TransactionProduct::query()
                ->join('transactions', 'transcation_products.transaction_id', '=', 'transactions.id')
                ->join('products', 'transcation_products.product_id', '=', 'products.id')
                ->where('transactions.status', 'completed')
                ->tap(fn ($query) => $this->applyCompletedDateRange($query, $this->getStartDate(), $this->getEndDate()))
                ->groupBy('products.id', 'products.name')
                ->selectRaw('
                    products.id,
                    products.name,
                    SUM(transcation_products.quantity) as total_quantity,
                    SUM((transcation_products.price * transcation_products.quantity) - transcation_products.discount) as total_revenue
                ')
                ->orderByDesc('total_quantity')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    public function getLowStockProducts(int $threshold = 10): array
    {
        return Cache::remember($this->getCacheKey('lowstock'), $this->cacheSeconds, function () {
            return Product::where('is_active', true)
                ->whereColumn('stock', '<=', 'security_stock')
                ->with('category')
                ->limit(10)
                ->get()
                ->toArray();
        });
    }

    public function getLowStockCount(): int
    {
        return Cache::remember($this->getCacheKey('lowstockcount'), $this->cacheSeconds, function () {
            return Product::where('is_active', true)
                ->whereColumn('stock', '<=', 'security_stock')
                ->count();
        });
    }

    protected function getCombinedStats(): array
    {
        if ($this->combinedStats !== null) {
            return $this->combinedStats;
        }

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();
        $prevPeriod = $this->getPreviousPeriod();

        $current = TransactionProduct::query()
            ->join('transactions', 'transcation_products.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'completed')
            ->tap(fn ($query) => $this->applyCompletedDateRange($query, $startDate, $endDate))
            ->selectRaw('
                SUM((transcation_products.price * transcation_products.quantity) - transcation_products.discount) as revenue,
                COUNT(DISTINCT transactions.id) as orders
            ')
            ->first();

        $previous = TransactionProduct::query()
            ->join('transactions', 'transcation_products.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'completed')
            ->tap(fn ($query) => $this->applyCompletedDateRange($query, $prevPeriod['start'], $prevPeriod['end']))
            ->selectRaw('
                SUM((transcation_products.price * transcation_products.quantity) - transcation_products.discount) as revenue,
                COUNT(DISTINCT transactions.id) as orders
            ')
            ->first();

        $this->combinedStats = [
            'current_revenue' => $current->revenue ?? 0,
            'current_orders' => $current->orders ?? 0,
            'previous_revenue' => $previous->revenue ?? 0,
            'previous_orders' => $previous->orders ?? 0,
        ];

        return $this->combinedStats;
    }

    protected function getDailySales(Carbon $startDate, Carbon $endDate): array
    {
        $days = $startDate->diffInDays($endDate) + 1;
        
        $results = TransactionProduct::query()
            ->join('transactions', 'transcation_products.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'completed')
            ->tap(fn ($query) => $this->applyCompletedDateRange($query, $startDate, $endDate))
            ->selectRaw('DATE(IFNULL(transactions.complete_date, transactions.created_at)) as date, SUM((transcation_products.price * transcation_products.quantity) - transcation_products.discount) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $labels = [];
        $values = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $labels[] = $startDate->copy()->addDays($i)->format('D, M d');
            $values[] = round($results[$date] ?? 0, 2);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    protected function getPreviousPeriod(): array
    {
        if ($this->previousPeriod !== null) {
            return $this->previousPeriod;
        }

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();
        
        if (!$startDate || !$endDate) {
            $days = 7;
            $this->previousPeriod = [
                'start' => now()->subDays($days * 2)->startOfDay(),
                'end' => now()->subDays($days)->endOfDay(),
            ];

            return $this->previousPeriod;
        }

        $days = $startDate->diffInDays($endDate);
        $this->previousPeriod = [
            'start' => $startDate->copy()->subDays($days + 1)->startOfDay(),
            'end' => $startDate->copy()->subDay()->endOfDay(),
        ];

        return $this->previousPeriod;
    }

    protected function applyCompletedDateRange($query, Carbon $startDate, Carbon $endDate): void
    {
        $query->where(function ($outer) use ($startDate, $endDate) {
            $outer
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereNotNull('transactions.complete_date')
                        ->whereBetween('transactions.complete_date', [$startDate, $endDate]);
                })
                ->orWhere(function ($q) use ($startDate, $endDate) {
                    $q->whereNull('transactions.complete_date')
                        ->whereBetween('transactions.created_at', [$startDate, $endDate]);
                });
        });
    }

    protected function calculatePercentChange(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }
}
