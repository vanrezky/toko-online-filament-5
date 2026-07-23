<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogPostRelatedResource extends JsonResource
{
    /**
     * Transform a related article into the compact payload used by the article reading queue.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'published_at' => $this->published_at?->format('M d, Y'),
            'image_url' => $this->image ? asset('storage/' . $this->image) : null,
            'category' => [
                'name' => $this->category?->name,
                'slug' => $this->category?->slug,
            ],
        ];
    }
}
