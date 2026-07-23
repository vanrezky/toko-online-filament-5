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
        $baseUnitPrice = (float) $this->price;
        $discountPerUnit = (float) $this->discount;
        $finalLineSubtotal = (float) $this->subtotal;
        $baseLineSubtotal = $baseUnitPrice * (int) $this->quantity;

        if ($discountPerUnit > 0 && abs($finalLineSubtotal - $baseLineSubtotal) < 0.01) {
            $baseUnitPrice += $discountPerUnit;
        }

        $finalUnitPrice = $this->quantity > 0
            ? $finalLineSubtotal / (int) $this->quantity
            : max(0, $baseUnitPrice - $discountPerUnit);

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
            'price' => $baseUnitPrice,
            'discount' => $discountPerUnit,
            'final_price' => $finalUnitPrice,
            'total' => $this->subtotal,
            'description' => $this->description,
            'reviewed' => $this->review !== null,
        ];
    }
}
