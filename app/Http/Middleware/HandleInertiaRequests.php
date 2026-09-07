<?php

namespace App\Http\Middleware;

use App\Enums\CartStatus;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CustomerResource;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Page;
use App\Models\Wishlist;
use App\Services\CacheService;
use App\Services\TemplateService;
use App\Settings\GeneralSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'frontend';

    public function __construct(private readonly TemplateService $templateService) {}

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    protected function isFrontendRequest(Request $request): bool
    {
        $path = $request->path();

        return ! str_starts_with($path, 'admin')
            && ! str_starts_with($path, 'filament')
            && ! str_starts_with($path, '_debugbar');
    }

    public function share(Request $request): array
    {
        $isFrontend = $this->isFrontendRequest($request);

        $shared = [
            'settings' => function () {
                $settings = app(GeneralSettings::class);

                return [
                    'logo' => $settings->getLogo() ?? '',
                    'favicon' => $settings->getFavicon() ?? '',
                    'site_name' => $settings->site_name ?? '',
                    'is_private_store' => (bool) ($settings->is_private_store ?? false),
                    'term_agreement' => (bool) ($settings->term_agreement ?? false),
                'site_description' => $settings->site_description ?? '',
                'site_keywords' => $settings->site_keywords ?? '',
                'social_title' => $settings->social_title ?? '',
                'social_description' => $settings->social_description ?? '',
                'social_image' => $settings->social_image ? getUrlImage($settings->social_image) : '',
                'email' => $settings->email ?? '',
                'phone' => $settings->phone ?? '',
                'wa_phone' => $settings->wa_phone ?? '',
                'address' => $settings->address ?? '',
                'instagram' => $settings->instagram ?? '',
                'facebook' => $settings->facebook ?? '',
                'twitter' => $settings->twitter ?? '',
            ];
            },
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
            ],
        ];

        if (! $isFrontend) {
            return array_merge(parent::share($request), $shared);
        }

        return array_merge(parent::share($request), $shared, [
            'auth' => [
                'user' => Auth::guard('customer')->user() ? CustomerResource::make(Auth::guard('customer')->user()) : null,
            ],
            'wishlist_product_ids' => function () {
                if (Auth::guard('customer')->check()) {
                    return Wishlist::where('customer_id', Auth::guard('customer')->id())
                        ->with('product:id,uuid')
                        ->get()
                        ->pluck('product.uuid')
                        ->toArray();
                }

                return [];
            },
            'cart_total' => function () {
                if (Auth::guard('customer')->check()) {
                    return CartItem::with('cart[id,customer_id,status]')->whereHas('cart', fn ($Q) => $Q->where(
                        [
                            'customer_id' => Auth::guard('customer')->id(),
                            'status' => CartStatus::Active,
                        ]
                    ))->count();
                }

                return 0;
            },
            'menu' => function () {
                return CacheService::rememberManaged('frontend', 'frontend_menu', 3600, function () {
                    return [
                        'header' => Page::headerMenu()->get()->map(fn ($page) => [
                            'name' => $page->title,
                            'href' => route('frontend.page.show', $page->slug),
                        ]),
                        'footer' => Page::footerMenu()->get()->map(fn ($page) => [
                            'name' => $page->title,
                            'href' => route('frontend.page.show', $page->slug),
                        ]),
                    ];
                });
            },
            'colorScheme' => fn () => $this->templateService->getColorScheme(),
        ]);
    }
}
