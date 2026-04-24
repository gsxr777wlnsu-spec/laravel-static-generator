<?php

namespace App\Repositories;

use App\Contracts\PageRepositoryInterface;
use App\Models\Page;
use App\Models\Site;
use Illuminate\Database\Eloquent\Collection;

class PageRepository implements PageRepositoryInterface
{
    public function create(array $data): Page
    {
        return Page::create($data);
    }

    public function update(Page $page, array $data): Page
    {
        $page->update($data);
        return $page->fresh();
    }

    public function delete(Page $page): bool
    {
        return $page->delete();
    }

    public function findBySlug(Site $site, string $slug): ?Page
    {
        return Page::where('site_id', $site->id)
            ->where('slug', $slug)
            ->first();
    }

    public function getActiveBySite(Site $site): Collection
    {
        return Page::where('site_id', $site->id)
            ->where('status', 'published')
            ->get();
    }
}
