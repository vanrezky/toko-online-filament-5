<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SliderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // 'id' => $this->id,
            'eyebrow' => $this->eyebrow,
            'title' => $this->title,
            'description' => $this->description,
            'image_url' => $this->image_url ?: null,
            'href' => $this->target_link,
            'target_anchor' => $this->button_label,
        ];
    }
}
