<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\BlogPostStatus;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Faq;
use App\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class ContentRepository
{
    public function paginatePublishedPosts(?string $category, ?string $search, ?string $tag): LengthAwarePaginator
    {
        $query = BlogPost::query()
            ->where('is_status', BlogPostStatus::PUBLISHED)
            ->with(['category', 'author', 'tags', 'meta'])
            ->latest('published_at');

        if ($category !== null && $category !== '') {
            $query->whereHas('category', fn (Builder $categoryQuery): Builder => $categoryQuery->where('slug', $category));
        }

        if ($search !== null && $search !== '') {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('title', 'like', '%'.$search.'%')
                    ->orWhere('content', 'like', '%'.$search.'%');
            });
        }

        if ($tag !== null && $tag !== '') {
            $query->withAnyTags([$tag]);
        }

        return $query->paginate(9)->withQueryString();
    }

    /** @return Collection<int, BlogCategory> */
    public function visibleBlogCategories(): Collection
    {
        return BlogCategory::query()->where('is_visible', true)->get();
    }

    public function findPublishedPost(string $slug): BlogPost
    {
        return BlogPost::query()
            ->where('slug', $slug)
            ->where('is_status', BlogPostStatus::PUBLISHED)
            ->with(['category', 'author', 'tags', 'meta'])
            ->firstOrFail();
    }

    public function incrementViews(BlogPost $post): void
    {
        $post->increment('views');
    }

    /** @return Collection<int, BlogPost> */
    public function relatedPosts(BlogPost $post): Collection
    {
        $tagIds = $post->tags->modelKeys();

        return BlogPost::query()
            ->select(['id', 'blog_category_id', 'title', 'slug', 'published_at', 'image'])
            ->whereKeyNot($post->getKey())
            ->where('is_status', BlogPostStatus::PUBLISHED)
            ->with('category:id,name,slug')
            ->where(function (Builder $query) use ($post, $tagIds): void {
                $query->where('blog_category_id', $post->blog_category_id);

                if ($tagIds !== []) {
                    $query->orWhereHas('tags', fn (Builder $tagQuery): Builder => $tagQuery->whereIn('tags.id', $tagIds));
                }
            })
            ->when(
                $tagIds !== [],
                fn (Builder $query): Builder => $query->withCount([
                    'tags as shared_tags_count' => fn (Builder $tagQuery): Builder => $tagQuery->whereIn('tags.id', $tagIds),
                ])->orderByDesc('shared_tags_count'),
            )
            ->orderByRaw('blog_category_id = ? desc', [$post->blog_category_id])
            ->latest('published_at')
            ->limit(4)
            ->get();
    }

    public function findActivePage(string $slug): Page
    {
        return Page::query()->where('slug', $slug)->active()->with('meta')->firstOrFail();
    }

    /** @return Collection<int, Faq> */
    public function allFaqs(): Collection
    {
        return Faq::query()->get();
    }
}
