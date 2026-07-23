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
        return [
            'id' => $this->id,
            'discount_percentage' => (float) $this->discount_percentage,
            'stock' => (int) $this->stock,
            'product' => ProductSimpleResource::make($this->whenLoaded('product')),
        ];
    }
}
