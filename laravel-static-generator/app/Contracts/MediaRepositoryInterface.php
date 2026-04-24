<?php

namespace App\Contracts;

use App\Models\Media;
use App\Models\Site;
use Illuminate\Database\Eloquent\Collection;

interface MediaRepositoryInterface
{
    public function create(array $data): Media;
    public function update(Media $media, array $data): Media;
    public function delete(Media $media): bool;
    public function getBySite(Site $site): Collection;
}
