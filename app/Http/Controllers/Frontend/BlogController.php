<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\BlogPostStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\BlogPostRelatedResource;
use App\Http\Resources\BlogPostResource;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $query = BlogPost::where('is_status', BlogPostStatus::PUBLISHED)
            ->with(['category', 'author', 'tags', 'meta'])
            ->latest('published_at');

        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('content', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('tag')) {
            $query->withAnyTags([$request->tag]);
        }

        $posts = $query->paginate(9)->withQueryString();
        $categories = BlogCategory::where('is_visible', true)->get();

        return Inertia::render('Blog/Index', [
            'posts' => BlogPostResource::collection($posts),
            'categories' => $categories->map(fn($cat) => [
                'name' => $cat->name,
                'slug' => $cat->slug,
            ]),
            'filters' => $request->only(['category', 'search', 'tag']),
        ]);
    }

    public function show($slug)
    {
        $post = BlogPost::where('slug', $slug)
            ->where('is_status', BlogPostStatus::PUBLISHED)
            ->with(['category', 'author', 'tags', 'meta'])
            ->firstOrFail();

        // Increment views
        $post->increment('views');

        $tagIds = $post->tags->modelKeys();

        $relatedPosts = BlogPost::query()
            ->select(['id', 'blog_category_id', 'title', 'slug', 'published_at', 'image'])
            ->whereKeyNot($post->getKey())
            ->where('is_status', BlogPostStatus::PUBLISHED)
            ->with('category:id,name,slug')
            ->where(function ($query) use ($post, $tagIds) {
                $query->where('blog_category_id', $post->blog_category_id);

                if ($tagIds !== []) {
                    $query->orWhereHas('tags', fn ($tagQuery) => $tagQuery->whereIn('tags.id', $tagIds));
                }
            })
            ->when(
                $tagIds !== [],
                fn ($query) => $query->withCount([
                    'tags as shared_tags_count' => fn ($tagQuery) => $tagQuery->whereIn('tags.id', $tagIds),
                ])->orderByDesc('shared_tags_count'),
            )
            ->orderByRaw('blog_category_id = ? desc', [$post->blog_category_id])
            ->latest('published_at')
            ->limit(4)
            ->get();

        return Inertia::render('Blog/Show', [
            'post' => BlogPostResource::make($post),
            'relatedPosts' => BlogPostRelatedResource::collection($relatedPosts),
        ]);
    }
}
