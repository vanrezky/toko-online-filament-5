<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $name = $this->is_anonymous
            ? 'Anonim'
            : ($this->reviewer_name ?: $this->customer?->full_name ?: 'Customer');

        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'review' => $this->review,
            'reviewer_name' => $name,
            'is_anonymous' => $this->is_anonymous,
            'is_admin' => $this->is_admin,
            'created_at' => $this->created_at?->toDateString(),
            'images' => $this->getMedia('images')->map(fn ($media) => $media->getUrl('thumb'))->values(),
        ];
    }
}
