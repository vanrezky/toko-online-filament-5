<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $productName = $this->display_product_name;
        $variantName = $this->display_variant_name;
        $variantSku = $this->variant_sku ?? data_get($this->product_snapshot, 'variant_sku');
        $productCode = $this->product_code ?? data_get($this->product_snapshot, 'product_code');
        $featuredImageUrl = data_get($this->product_snapshot, 'featured_image_url') ?: asset('images/placeholders/product-snapshot.svg');

        return [
            'id' => $this->uuid,
            'product_id' => $this->product_id,
            'warehouse_id' => $this->warehouse_id,
            'product' => [
                'name' => $productName,
                'code' => $productCode,
                'thumbnail' => $featuredImageUrl,
            ],
            'product_name' => $productName,
            'product_code' => $productCode,
            'product_thumbnail' => $featuredImageUrl,
            'product_variant' => $variantName || $variantSku ? [
                'id' => null,
                'variant_name' => $variantName,
                'sku' => $variantSku,
            ] : null,
            'variant_name' => $variantName,
            'variant_sku' => $variantSku,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'discount' => $this->discount,
            'total' => $this->subtotal,
            'description' => $this->description,
        ];
    }
}
