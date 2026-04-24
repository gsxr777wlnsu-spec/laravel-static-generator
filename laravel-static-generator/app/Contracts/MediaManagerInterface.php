<?php

namespace App\Contracts;

use App\Models\Media;
use App\Models\Site;
use Illuminate\Http\UploadedFile;

interface MediaManagerInterface
{
    public function upload(UploadedFile $file, Site $site, string $alt, ?string $title = null): Media;
    public function delete(Media $media): bool;
    public function validateFile(UploadedFile $file): array;
    public function discoverExistingMedia(Site $site): int;
}
