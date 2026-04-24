<?php

namespace App\Services;

use App\Contracts\ImageProcessorInterface;
use App\Contracts\MediaRepositoryInterface;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageProcessorService implements ImageProcessorInterface
{
    private ImageManager $imageManager;

    public function __construct(
        private MediaRepositoryInterface $repository
    ) {
        $this->imageManager = new ImageManager(new Driver());
    }

    public function generateWebP(Media $media): string
    {
        if (!in_array($media->mime_type, ['image/jpeg', 'image/png'])) {
            throw new \InvalidArgumentException('WebP generation only supported for JPEG and PNG images');
        }

        $originalPath = Storage::disk('sites')->path($media->path);
        
        if (!file_exists($originalPath)) {
            throw new \RuntimeException('Original image file not found');
        }

        $image = $this->imageManager->read($originalPath);
        
        $webpFilename = pathinfo($media->path, PATHINFO_FILENAME) . '.webp';
        $webpPath = dirname($media->path) . '/' . $webpFilename;
        
        $webpFullPath = Storage::disk('sites')->path($webpPath);
        
        $image->toWebp(90)->save($webpFullPath);
        
        $this->repository->update($media, ['webp_path' => $webpPath]);
        
        return $webpPath;
    }

    public function resize(Media $media, int $width, int $height, bool $preserveAspectRatio = true): Media
    {
        $originalPath = Storage::disk('sites')->path($media->path);
        
        if (!file_exists($originalPath)) {
            throw new \RuntimeException('Original image file not found');
        }

        $image = $this->imageManager->read($originalPath);
        
        if ($preserveAspectRatio) {
            $image->scale($width, $height);
        } else {
            $image->resize($width, $height);
        }
        
        $resizedFilename = pathinfo($media->path, PATHINFO_FILENAME) . "_{$width}x{$height}." . pathinfo($media->path, PATHINFO_EXTENSION);
        $resizedPath = dirname($media->path) . '/' . $resizedFilename;
        
        $resizedFullPath = Storage::disk('sites')->path($resizedPath);
        
        $image->save($resizedFullPath);
        
        $newMedia = $this->repository->create([
            'site_id' => $media->site_id,
            'path' => $resizedPath,
            'alt' => $media->alt,
            'title' => $media->title,
            'width' => $width,
            'height' => $height,
            'size' => filesize($resizedFullPath),
            'mime_type' => $media->mime_type,
        ]);
        
        return $newMedia;
    }
}
