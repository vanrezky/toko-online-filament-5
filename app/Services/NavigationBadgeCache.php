<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\Customer;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Reseller;
use App\Models\Faq;
use App\Models\BlogPost;
use App\Models\BlogCategory;
use App\Models\Page;
use Illuminate\Support\Facades\Cache;

class NavigationBadgeCache
{
    protected static int $cacheSeconds = 60;

    public static function getTransactionNotShippedCount(): int
    {
        return Cache::remember('nav_transaction_not_shipped', self::$cacheSeconds, function () {
            return Transaction::whereIn('status', [
                TransactionStatus::packed->value,
            ])->count();
        });
    }

    public static function getTransactionCountByStatus(TransactionStatus $status): int
    {
        return Cache::remember("nav_transaction_status_{$status->value}", self::$cacheSeconds, function () use ($status) {
            return Transaction::where('status', $status->value)->count();
        });
    }

    public static function getCustomerCount(): int
    {
        return Cache::remember('nav_customer_count', self::$cacheSeconds, function () {
            return Customer::count();
        });
    }

    public static function getUserCount(): int
    {
        return Cache::remember('nav_user_count', self::$cacheSeconds, function () {
            return User::count();
        });
    }

    public static function getWarehouseCount(): int
    {
        return Cache::remember('nav_warehouse_count', self::$cacheSeconds, function () {
            return Warehouse::where('is_active', true)->count();
        });
    }

    public static function getResellerCount(): int
    {
        return Cache::remember('nav_reseller_count', self::$cacheSeconds, function () {
            return Reseller::where('is_active', true)->count();
        });
    }

    public static function getFaqCount(): int
    {
        return Cache::remember('nav_faq_count', self::$cacheSeconds, function () {
            return Faq::count();
        });
    }

    public static function getBlogPostCount(): int
    {
        return Cache::remember('nav_blog_post_count', self::$cacheSeconds, function () {
            return BlogPost::active()->count();
        });
    }

    public static function getBlogCategoryCount(): int
    {
        return Cache::remember('nav_blog_category_count', self::$cacheSeconds, function () {
            return BlogCategory::count();
        });
    }

    public static function getPageCount(): int
    {
        return Cache::remember('nav_page_count', self::$cacheSeconds, function () {
            return Page::active()->count();
        });
    }

    public static function forgetAll(): void
    {
        Cache::forget('nav_transaction_not_shipped');
        foreach (TransactionStatus::cases() as $status) {
            Cache::forget("nav_transaction_status_{$status->value}");
        }
        Cache::forget('nav_customer_count');
        Cache::forget('nav_user_count');
        Cache::forget('nav_warehouse_count');
        Cache::forget('nav_reseller_count');
        Cache::forget('nav_faq_count');
        Cache::forget('nav_blog_post_count');
        Cache::forget('nav_blog_category_count');
        Cache::forget('nav_page_count');
    }
}
