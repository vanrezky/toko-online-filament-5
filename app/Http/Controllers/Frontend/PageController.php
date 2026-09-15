<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\MetaResource;
use App\Services\ContentService;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(private readonly ContentService $contentService) {}

    public function show(string $slug): Response
    {
        $page = $this->contentService->findActivePage($slug);

        return Inertia::render('Page/Show', [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'content' => $page->content,
                'image_url' => $page->image ? Storage::disk(config('filesystems.upload_disk', 'public'))->url($page->image) : null,
                'meta' => MetaResource::make($page->meta),
            ],
        ]);
    }
}
