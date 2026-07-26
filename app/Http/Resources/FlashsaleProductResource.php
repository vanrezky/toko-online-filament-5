<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlashsaleProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pricing = $this->relationLoaded('product') && $this->product
            ? app(\App\Services\FlashsalePricingService::class)->resolveFlashsale($this->product, null, $this->resource)
            : null;

        return [
            'id' => $this->id,
            'discount_percentage' => (float) $this->discount_percentage,
            'stock' => (int) $this->stock,
            'pricing' => $pricing ? [
                'original_price' => $pricing['original_price'],
                'final_price' => $pricing['price'],
                'discount' => $pricing['discount'],
                'source' => $pricing['source'],
            ] : null,
            'product' => ProductSimpleResource::make($this->whenLoaded('product')),
        ];
    }
}
