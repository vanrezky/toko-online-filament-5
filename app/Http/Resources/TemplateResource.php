<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'color_scheme' => $this->color_scheme,
            'sections' => $this->whenLoaded('sections', fn () => $this->sections
                ->map(fn ($section) => [
                    'uuid' => $section->uuid,
                    'name' => $section->name,
                    'type' => $section->type,
                    'description' => $section->description,
                    // 'icon' => $section->icon,
                    'contents' => $section->contents
                        ->mapWithKeys(fn ($content) => [$content->field?->key ?? 'unknown' => $content->value])
                        ->all(),
                ])
                ->values()
                ->all()),
        ];
    }
}
