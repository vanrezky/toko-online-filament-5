<?php

namespace App\Http\Resources;

use App\Services\FlashsalePricingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductSimpleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pricing = app(FlashsalePricingService::class)->resolve($this->resource, null, 1);
        $customer = auth('customer')->user();
        $resellerId = $customer?->reseller_id;

        $targetPrice = $this->price;
        if ($resellerId) {
            $resellerPrice = $this->resellerPrices->where('reseller_id', $resellerId)->first();
            if ($resellerPrice) {
                $targetPrice = $resellerPrice->price;
            }
        }

        $discountPercentage = null;
        if ($this->sale_price && $targetPrice > 0 && $this->sale_price < $targetPrice) {
            $discountPercentage = round((($targetPrice - $this->sale_price) / $targetPrice) * 100);
        }

        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'digital' => $this->digital,
            'code' => $this->code,
            'stock' => $this->stock,
            'is_new' => $this->created_at?->gte(now()->subDays(30)) ?? false,
            'sale_price' => $this->sale_price,
            'price' => $targetPrice,
            'discount_percentage' => $discountPercentage,
            'pricing' => [
                'original_price' => $pricing['original_price'],
                'final_price' => $pricing['price'],
                'discount' => $pricing['discount'],
                'source' => $pricing['source'],
                'flashsale' => $pricing['flashsale_product_id'] ? [
                    'id' => $pricing['flashsale_product_id'],
                    'discount_percentage' => $pricing['flashsale_discount_percentage'],
                    'stock' => $pricing['flashsale_stock'],
                ] : null,
            ],
            'min_order' => $this->min_order,
            'thumbnail' => $this->resource->getMedia()->first()?->getUrl('thumb'),
            'currency' => settings('currency_text', 'Rp'),
            'rating_average' => round((float) ($this->reviews_avg_rating ?? 0), 1),
            'review_count' => (int) ($this->reviews_count ?? 0),
            'sold_count' => (int) ($this->completed_sold_count ?? 0) + (int) $this->fake_sold_count,
        ];
    }
}
