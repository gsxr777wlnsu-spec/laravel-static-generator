<?php

namespace App\Repositories;

use App\Contracts\MediaRepositoryInterface;
use App\Models\Media;
use App\Models\Site;
use Illuminate\Database\Eloquent\Collection;

class MediaRepository implements MediaRepositoryInterface
{
    public function create(array $data): Media
    {
        return Media::create($data);
    }

    public function update(Media $media, array $data): Media
    {
        $media->update($data);
        return $media->fresh();
    }

    public function delete(Media $media): bool
    {
        return $media->delete();
    }

    public function getBySite(Site $site): Collection
    {
        return Media::where('site_id', $site->id)->get();
    }
}
