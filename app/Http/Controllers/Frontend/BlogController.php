<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogPostRelatedResource;
use App\Http\Resources\BlogPostResource;
use App\Services\ContentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function __construct(private readonly ContentService $contentService) {}

    public function index(Request $request): Response
    {
        $category = $request->filled('category') ? (string) $request->input('category') : null;
        $search = $request->filled('search') ? (string) $request->input('search') : null;
        $tag = $request->filled('tag') ? (string) $request->input('tag') : null;
        $posts = $this->contentService->paginatePublishedPosts($category, $search, $tag);
        $categories = $this->contentService->visibleBlogCategories();

        return Inertia::render('Blog/Index', [
            'posts' => BlogPostResource::collection($posts),
            'categories' => $categories->map(fn ($cat) => [
                'name' => $cat->name,
                'slug' => $cat->slug,
            ]),
            'filters' => $request->only(['category', 'search', 'tag']),
        ]);
    }

    public function show(string $slug): Response
    {
        $result = $this->contentService->showBlogPost($slug);

        return Inertia::render('Blog/Show', [
            'post' => BlogPostResource::make($result['post']),
            'relatedPosts' => BlogPostRelatedResource::collection($result['relatedPosts']),
        ]);
    }
}
