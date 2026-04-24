<?php

namespace App\Contracts;

use App\Models\Media;

interface ImageProcessorInterface
{
    public function generateWebP(Media $media): string;
    public function resize(Media $media, int $width, int $height, bool $preserveAspectRatio = true): Media;
}
