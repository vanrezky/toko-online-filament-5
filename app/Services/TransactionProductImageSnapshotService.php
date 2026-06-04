<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class TransactionProductImageSnapshotService
{
    public function snapshotFeaturedImage(?Product $product): array
    {
        if (! $product) {
            return [
                'featured_image_path' => null,
                'featured_image_url' => null,
            ];
        }

        $media = $product->getMedia()->first();

        if (! $media) {
            return [
                'featured_image_path' => null,
                'featured_image_url' => null,
            ];
        }

        $sourcePath = $media->hasGeneratedConversion('thumb')
            ? $media->getPath('thumb')
            : $media->getPath();

        if (! is_string($sourcePath) || ! is_file($sourcePath)) {
            return [
                'featured_image_path' => null,
                'featured_image_url' => null,
            ];
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg';
        $hash = sha1_file($sourcePath) ?: sha1($sourcePath);
        $targetPath = "snapshots/products/featured/{$hash}.{$extension}";

        if (! Storage::disk('public')->exists($targetPath)) {
            Storage::disk('public')->put($targetPath, file_get_contents($sourcePath));
        }

        return [
            'featured_image_path' => $targetPath,
            'featured_image_url' => Storage::disk('public')->url($targetPath),
        ];
    }
}
