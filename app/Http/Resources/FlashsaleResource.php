<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlashsaleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'start_time' => optional($this->start_time)->toIso8601String(),
            'end_time' => optional($this->end_time)->toIso8601String(),
            'is_active' => (bool) $this->is_active,
            'products' => FlashsaleProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
