<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Customer;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Reseller;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Warehouse;

class NavigationBadgeCache
{
    protected static int $cacheSeconds = 60;

    public static function getTransactionNotShippedCount(): int
    {
        return CacheService::rememberManaged('navigation', 'nav_transaction_not_shipped', self::$cacheSeconds, function () {
            return Transaction::whereIn('status', [
                TransactionStatus::packed->value,
            ])->count();
        });
    }

    public static function getTransactionCountByStatus(TransactionStatus $status): int
    {
        return CacheService::rememberManaged('navigation', "nav_transaction_status_{$status->value}", self::$cacheSeconds, function () use ($status) {
            return Transaction::where('status', $status->value)->count();
        });
    }

    public static function getCustomerCount(): int
    {
        return CacheService::rememberManaged('navigation', 'nav_customer_count', self::$cacheSeconds, function () {
            return Customer::count();
        });
    }

    public static function getUserCount(): int
    {
        return CacheService::rememberManaged('navigation', 'nav_user_count', self::$cacheSeconds, function () {
            return User::count();
        });
    }

    public static function getWarehouseCount(): int
    {
        return CacheService::rememberManaged('navigation', 'nav_warehouse_count', self::$cacheSeconds, function () {
            return Warehouse::where('is_active', true)->count();
        });
    }

    public static function getResellerCount(): int
    {
        return CacheService::rememberManaged('navigation', 'nav_reseller_count', self::$cacheSeconds, function () {
            return Reseller::where('is_active', true)->count();
        });
    }

    public static function getFaqCount(): int
    {
        return CacheService::rememberManaged('navigation', 'nav_faq_count', self::$cacheSeconds, function () {
            return Faq::count();
        });
    }

    public static function getBlogPostCount(): int
    {
        return CacheService::rememberManaged('navigation', 'nav_blog_post_count', self::$cacheSeconds, function () {
            return BlogPost::active()->count();
        });
    }

    public static function getBlogCategoryCount(): int
    {
        return CacheService::rememberManaged('navigation', 'nav_blog_category_count', self::$cacheSeconds, function () {
            return BlogCategory::count();
        });
    }

    public static function getPageCount(): int
    {
        return CacheService::rememberManaged('navigation', 'nav_page_count', self::$cacheSeconds, function () {
            return Page::active()->count();
        });
    }

    public static function forgetAll(): void
    {
        CacheService::forgetManaged('navigation', 'nav_transaction_not_shipped');
        foreach (TransactionStatus::cases() as $status) {
            CacheService::forgetManaged('navigation', "nav_transaction_status_{$status->value}");
        }
        CacheService::forgetManaged('navigation', 'nav_customer_count');
        CacheService::forgetManaged('navigation', 'nav_user_count');
        CacheService::forgetManaged('navigation', 'nav_warehouse_count');
        CacheService::forgetManaged('navigation', 'nav_reseller_count');
        CacheService::forgetManaged('navigation', 'nav_faq_count');
        CacheService::forgetManaged('navigation', 'nav_blog_post_count');
        CacheService::forgetManaged('navigation', 'nav_blog_category_count');
        CacheService::forgetManaged('navigation', 'nav_page_count');
    }
}
