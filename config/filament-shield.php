<?php

use App\Filament\Clusters\ContentCluster;
use App\Filament\Clusters\CustomerCluster;
use App\Filament\Clusters\MonitoringCluster;
use App\Filament\Clusters\ObservabilityCluster;
use App\Filament\Clusters\PromotionCluster;
use App\Filament\Clusters\RegionCluster;
use App\Filament\Pages\CacheManagement;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\ObservabilityExecutionDetail;
use App\Filament\Pages\ObservabilityExecutions;
use App\Filament\Pages\ObservabilityOverview;
use App\Filament\Pages\ObservabilityServiceMap;
use App\Filament\Pages\ObservabilityTraceDetail;
use App\Filament\Pages\ObservabilityTraces;
use App\Filament\Pages\QueueMonitor;
use App\Filament\Pages\SystemHealth;
use App\Filament\Resources\Balances\BalanceResource;
use App\Filament\Resources\NewsletterSubscribers\NewsletterSubscriberResource;
use App\Filament\Resources\Resellers\ResellerResource;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;

return [

    'shield_resource' => [
        'slug' => 'roles',
        'show_model_path' => true,
        'cluster' => null,
        'tabs' => [
            'pages' => true,
            'widgets' => true,
            'resources' => true,
            'custom_permissions' => false,
        ],
    ],

    'tenant_model' => null,

    'auth_provider_model' => 'App\\Models\\User',

    'super_admin' => [
        'enabled' => true,
        'name' => 'super_admin',
        'define_via_gate' => false,
        'intercept_gate' => 'before',
    ],

    'panel_user' => [
        'enabled' => true,
        'name' => 'panel_user',
    ],

    'permissions' => [
        'separator' => ':',
        'case' => 'pascal',
        'generate' => true,
    ],

    'policies' => [
        'path' => app_path('Policies'),
        'merge' => true,
        'generate' => false,
        'methods' => [
            'view',
            'create',
            'update',
            'delete',
        ],
        'single_parameter_methods' => [
            'create',
        ],
    ],

    'localization' => [
        'enabled' => false,
        'key' => 'filament-shield::filament-shield.resource_permission_prefixes_labels',
    ],

    'resources' => [
        'subject' => 'model',
        'manage' => [
            RoleResource::class => [
                'view',
                'create',
                'update',
                'delete',
            ],
        ],
        'exclude' => [
            ResellerResource::class,
        ],
    ],

    'pages' => [
        'subject' => 'class',
        'prefix' => 'view',
        'exclude' => [
            Dashboard::class,
            ObservabilityCluster::class,
            MonitoringCluster::class,
            ContentCluster::class,
            RegionCluster::class,
            CustomerCluster::class,
            PromotionCluster::class,
            //monitor,
            QueueMonitor::class,
            CacheManagement::class,
            ObservabilityExecutionDetail::class,
            ObservabilityExecutions::class,
            ObservabilityTraces::class,
            ObservabilityOverview::class,
            ObservabilityServiceMap::class,
            ObservabilityTraceDetail::class,
            SystemHealth::class


        ],
    ],

    'widgets' => [
        'subject' => 'class',
        'prefix' => 'view',
        'exclude' => [
            AccountWidget::class,
            FilamentInfoWidget::class,
        ],
    ],

    'custom_permissions' => [],

    'discovery' => [
        'discover_all_resources' => false,
        'discover_all_widgets' => false,
        'discover_all_pages' => false,
    ],

    'register_role_policy' => true,

];
