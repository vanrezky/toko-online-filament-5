<?php

namespace App\Support\MediaLibrary;

use App\Constants\UploadPath;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\Slider;
use App\Models\Voucher;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;

class UploadPathGenerator extends DefaultPathGenerator
{
    protected function getBasePath(Media $media): string
    {
        $prefix = $this->prefixFor($media);

        return $prefix === null
            ? parent::getBasePath($media)
            : $prefix.'/'.$media->getKey();
    }

    private function prefixFor(Media $media): ?string
    {
        return match (true) {
            is_a($media->model_type, Product::class, true) => UploadPath::PRODUCT_UPLOAD_PATH,
            is_a($media->model_type, ProductVariant::class, true) => UploadPath::PRODUCT_UPLOAD_PATH,
            is_a($media->model_type, Category::class, true) => UploadPath::CATEGORY_UPLOAD_PATH,
            is_a($media->model_type, Voucher::class, true) => UploadPath::VOUCHER_UPLOAD_PATH,
            is_a($media->model_type, Slider::class, true) => UploadPath::SLIDER_UPLOAD_PATH,
            is_a($media->model_type, Customer::class, true) => UploadPath::PROFILE_UPLOAD_PATH,
            is_a($media->model_type, ProductReview::class, true) => UploadPath::REVIEW_UPLOAD_PATH,
            default => null,
        };
    }
}
