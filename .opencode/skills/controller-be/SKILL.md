---
name: controller-be
description: Create Laravel controllers following the project's patterns. Use this for Frontend controllers (Inertia), API controllers (JSON), or Admin controllers (Filament).
license: MIT
compatibility: opencode
metadata:
  category: backend
  framework: laravel
---

## What I do

I help create Laravel controllers following this project's established patterns.

## Controller Types & Locations

### 1. Frontend Controllers (Inertia.js)
**Location:** `app/Http/Controllers/Frontend/`
**Purpose:** Render Inertia pages with data

```php
<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request)
    {
        $products = Product::active()
            ->with(['media', 'category'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => ProductSimpleResource::collection($products),
            'filters' => $request->only(['category', 'search']),
        ]);
    }

    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->with(['media', 'category', 'faqs'])
            ->firstOrFail();

        return Inertia::render('ProductDetail/Index', [
            'product' => new ProductResource($product),
        ]);
    }
}
```

### 2. API Controllers (JSON)
**Location:** `app/Http/Controllers/Api/`
**Purpose:** Return JSON responses for AJAX/Vue

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    protected VoucherService $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    public function index(Request $request): JsonResponse
    {
        $vouchers = $this->voucherService->getPublicVouchers();

        return response()->json([
            'success' => true,
            'data' => $vouchers,
        ]);
    }

    public function validateCode(string $code, Request $request): JsonResponse
    {
        $result = $this->voucherService->validateVoucher($code);

        if (!$result->success) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $result->errorCode,
                    'message' => $result->errorMessage,
                ],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result->voucher,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:vouchers,code',
            'name' => 'required|string|max:255',
            'discount' => 'required|numeric|min:0',
        ]);

        $voucher = Voucher::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Voucher created successfully',
            'data' => $voucher,
        ], 201);
    }

    public function update(Request $request, Voucher $voucher): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'discount' => 'sometimes|numeric|min:0',
        ]);

        $voucher->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Voucher updated successfully',
            'data' => $voucher,
        ]);
    }

    public function destroy(Voucher $voucher): JsonResponse
    {
        $voucher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Voucher deleted successfully',
        ]);
    }
}
```

### 3. Controller Patterns

**Constructor Injection (always use):**
```php
protected ServiceName $serviceName;

public function __construct(ServiceName $serviceName)
{
    $this->serviceName = $serviceName;
}
```

**Inertia Response Pattern:**
```php
return Inertia::render('PageName/Index', [
    'key' => Resource::collection($data),
    'filters' => $request->only(['filter1', 'filter2']),
    'settings' => $settings,
]);
```

**API Success Response Pattern:**
```php
return response()->json([
    'success' => true,
    'data' => $data,
]);
```

**API Error Response Pattern:**
```php
return response()->json([
    'success' => false,
    'error' => [
        'code' => 'ERROR_CODE',
        'message' => 'Error description',
    ],
], 400); // or 401, 403, 404, 422, 500
```

**Validation Pattern:**
```php
$validated = $request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email',
    'price' => 'required|numeric|min:0',
    'is_active' => 'boolean',
    'category_id' => 'required|exists:categories,id',
]);
```

**Redirect with Flash:**
```php
return redirect()->route('frontend.home')->with('success', 'Operation completed!');
return redirect()->back()->with('error', 'Something went wrong.');
return redirect()->route('frontend.products', $request->only('category'));
```

**Route Model Binding:**
```php
// Using slug instead of id
public function show(string $slug)
{
    $product = Product::where('slug', $slug)->firstOrFail();
}

// Or using type-hint
public function update(Request $request, Product $product)
{
    $product->update($request->validated());
}
```

**Pagination Pattern:**
```php
$products = Product::active()
    ->with(['relations'])
    ->latest()
    ->paginate(12) // or 15, 24, etc
    ->withQueryString(); // Keep query params in pagination links
```

## Common Controller Methods

| Method | Purpose |
|--------|---------|
| `index()` | List all resources |
| `show($id/slug)` | Show single resource |
| `create()` | Show create form (Inertia) |
| `store(Request)` | Store new resource |
| `edit($id)` | Show edit form (Inertia) |
| `update(Request, $id)` | Update resource |
| `destroy($id)` | Delete resource |

## When to use me

- Creating new controllers
- Adding methods to existing controllers
- Implementing CRUD operations
- Creating API endpoints
- Working with Inertia responses

## Important Notes

- **Always inject Services** in constructor, never use `new Service()`
- Use `$request->validate()` or Form Request classes
- Use `$request->only()` to pass filters to Inertia
- Use `->withQueryString()` on paginated results
- Return proper HTTP status codes (200, 201, 400, 401, 403, 404, 422, 500)
- For API: always return `success` boolean in JSON response
- Use `firstOrFail()` for single resource lookups
- Use route model binding when possible
