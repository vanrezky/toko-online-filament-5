<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Faq;
use App\Models\Page;
use App\Repositories\ContentRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class ContentService
{
    public function __construct(private readonly ContentRepository $contentRepository) {}

    public function paginatePublishedPosts(?string $category, ?string $search, ?string $tag): LengthAwarePaginator
    {
        return $this->contentRepository->paginatePublishedPosts($category, $search, $tag);
    }

    /** @return Collection<int, BlogCategory> */
    public function visibleBlogCategories(): Collection
    {
        return $this->contentRepository->visibleBlogCategories();
    }

    /**
     * @return array{post: BlogPost, relatedPosts: Collection<int, BlogPost>}
     */
    public function showBlogPost(string $slug): array
    {
        $post = $this->contentRepository->findPublishedPost($slug);
        $this->contentRepository->incrementViews($post);

        return [
            'post' => $post,
            'relatedPosts' => $this->contentRepository->relatedPosts($post),
        ];
    }

    public function findActivePage(string $slug): Page
    {
        return $this->contentRepository->findActivePage($slug);
    }

    /** @return Collection<int, Faq> */
    public function allFaqs(): Collection
    {
        return $this->contentRepository->allFaqs();
    }
}
