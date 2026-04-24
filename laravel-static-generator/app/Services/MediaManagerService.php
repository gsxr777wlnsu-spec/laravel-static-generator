<?php

namespace App\Services;

use App\Contracts\MediaManagerInterface;
use App\Contracts\MediaRepositoryInterface;
use App\Jobs\GenerateWebPJob;
use App\Models\Media;
use App\Models\Site;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaManagerService implements MediaManagerInterface
{
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const WARNING_FILE_SIZE = 2 * 1024 * 1024; // 2MB

    public function __construct(
        private MediaRepositoryInterface $repository
    ) {}

    public function upload(UploadedFile $file, Site $site, string $alt, ?string $title = null): Media
    {
        $validation = $this->validateFile($file);
        
        if (!$validation['valid']) {
            throw new \InvalidArgumentException($validation['message']);
        }

        $filename = $this->generateUniqueFilename($file);
        $path = "{$site->id}/assets/images/upload/{$filename}";
        
        Storage::disk('sites')->put($path, file_get_contents($file->getRealPath()));

        $imageSize = getimagesize($file->getRealPath());
        
        $media = $this->repository->create([
            'site_id' => $site->id,
            'path' => $path,
            'alt' => $alt,
            'title' => $title,
            'width' => $imageSize[0] ?? null,
            'height' => $imageSize[1] ?? null,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
        
        if (in_array($file->getMimeType(), ['image/jpeg', 'image/png'])) {
            GenerateWebPJob::dispatch($media);
        }
        
        return $media;
    }

    public function delete(Media $media): bool
    {
        if (Storage::disk('sites')->exists($media->path)) {
            Storage::disk('sites')->delete($media->path);
        }
        
        if ($media->webp_path && Storage::disk('sites')->exists($media->webp_path)) {
            Storage::disk('sites')->delete($media->webp_path);
        }
        
        return $this->repository->delete($media);
    }

    public function validateFile(UploadedFile $file): array
    {
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            return [
                'valid' => false,
                'message' => 'Invalid file type. Allowed types: ' . implode(', ', self::ALLOWED_MIME_TYPES)
            ];
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return [
                'valid' => false,
                'message' => 'File size exceeds maximum allowed size of ' . (self::MAX_FILE_SIZE / 1024 / 1024) . 'MB'
            ];
        }

        $warning = null;
        if ($file->getSize() > self::WARNING_FILE_SIZE) {
            $warning = 'File size exceeds recommended size of ' . (self::WARNING_FILE_SIZE / 1024 / 1024) . 'MB';
        }

        return [
            'valid' => true,
            'message' => null,
            'warning' => $warning
        ];
    }

    private function generateUniqueFilename(UploadedFile $file): string
    {
        return uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
    }

    public function discoverExistingMedia(Site $site): int
    {
        $basePath = "{$site->id}/assets/images";
        
        if (!Storage::disk('sites')->exists($basePath)) {
            return 0;
        }

        $allFiles = Storage::disk('sites')->allFiles($basePath);
        $discovered = 0;

        foreach ($allFiles as $filePath) {
            // Skip hidden files
            if (str_starts_with(basename($filePath), '.')) {
                continue;
            }

            // Check if it's an image
            $mimeType = Storage::disk('sites')->mimeType($filePath);
            if (!str_starts_with($mimeType, 'image/')) {
                continue;
            }

            // Check if already in database
            $exists = Media::where('site_id', $site->id)
                ->where('path', $filePath)
                ->exists();

            if (!$exists) {
                $fullPath = Storage::disk('sites')->path($filePath);
                $imageSize = @getimagesize($fullPath);
                
                $this->repository->create([
                    'site_id' => $site->id,
                    'path' => $filePath,
                    'alt' => basename($filePath),
                    'width' => $imageSize[0] ?? null,
                    'height' => $imageSize[1] ?? null,
                    'size' => Storage::disk('sites')->size($filePath),
                    'mime_type' => $mimeType,
                ]);
                
                $discovered++;
            }
        }

        return $discovered;
    }
}
