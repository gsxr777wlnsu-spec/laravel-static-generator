<?php

namespace App\Contracts;

use App\Models\Page;
use App\Models\Site;
use Illuminate\Database\Eloquent\Collection;

interface PageRepositoryInterface
{
    public function create(array $data): Page;
    public function update(Page $page, array $data): Page;
    public function delete(Page $page): bool;
    public function findBySlug(Site $site, string $slug): ?Page;
    public function getActiveBySite(Site $site): Collection;
}
