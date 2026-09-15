<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\FaqResource;
use App\Services\ContentService;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function __construct(private readonly ContentService $contentService) {}

    public function __invoke(): Response
    {
        $faqs = $this->contentService->allFaqs();

        return Inertia::render('Faq/Index', [
            'faqs' => FaqResource::collection($faqs),
        ]);
    }
}
