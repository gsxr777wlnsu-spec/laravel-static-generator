<?php

namespace App\Contracts;

use App\Models\Site;
use Illuminate\Support\Collection;

interface SiteRepositoryInterface
{
    public function findById(int $id): ?Site;

    public function findByDomain(string $domain): ?Site;

    public function getAll(): Collection;

    public function getActive(): Collection;

    public function create(array $data): Site;

    public function update(Site $site, array $data): Site;

    public function delete(Site $site): bool;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLastCleanupIssues(): array;
}
