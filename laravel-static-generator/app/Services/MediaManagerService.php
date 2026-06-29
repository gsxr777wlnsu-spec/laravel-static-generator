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
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/x-webp', 'image/svg+xml', 'image/avif'];
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const WARNING_FILE_SIZE = 2 * 1024 * 1024; // 2MB

    public function __construct(
        private MediaRepositoryInterface $repository
    ) {}

    public function upload(UploadedFile $file, Site $site, string $alt, ?string $title = null, ?string $targetDirectory = null): Media
    {
        $validation = $this->validateFile($file);
        
        if (!$validation['valid']) {
            throw new \InvalidArgumentException($validation['message']);
        }

        $mimeType = $this->normalizeMimeType((string) $file->getMimeType());
        $filename = $this->generateUniqueFilename($file);
        $relativeDirectory = $this->normalizeTargetDirectory($targetDirectory);
        $path = "{$site->id}/{$relativeDirectory}/{$filename}";
        
        Storage::disk('sites')->put($path, file_get_contents($file->getRealPath()));

        $imageSize = @getimagesize($file->getRealPath());
        $width = is_array($imageSize) ? ($imageSize[0] ?? null) : null;
        $height = is_array($imageSize) ? ($imageSize[1] ?? null) : null;
        
        $media = $this->repository->create([
            'site_id' => $site->id,
            'path' => $path,
            'alt' => $alt,
            'title' => $title,
            'width' => $width,
            'height' => $height,
            'size' => $file->getSize(),
            'mime_type' => $mimeType,
        ]);
        
        if (in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
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
        $mimeType = $this->normalizeMimeType((string) $file->getMimeType());

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
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
        $extension = $this->resolveExtension($file);
        return uniqid() . '_' . time() . '.' . $extension;
    }

    private function resolveExtension(UploadedFile $file): string
    {
        $mimeType = $this->normalizeMimeType((string) $file->getMimeType());
        $expectedExtension = $this->extensionFromMimeType($mimeType);
        $originalExtension = strtolower(trim((string) $file->getClientOriginalExtension()));

        if ($originalExtension !== '' && $this->isExtensionCompatibleWithMimeType($originalExtension, $mimeType)) {
            return $originalExtension;
        }

        if ($expectedExtension !== null) {
            return $expectedExtension;
        }

        if ($originalExtension !== '') {
            return $originalExtension;
        }

        return 'bin';
    }

    private function isExtensionCompatibleWithMimeType(string $extension, string $mimeType): bool
    {
        $extension = strtolower(trim($extension));
        if ($extension === '') {
            return false;
        }

        return in_array($extension, $this->extensionsForMimeType($mimeType), true);
    }

    /**
     * @return list<string>
     */
    private function extensionsForMimeType(string $mimeType): array
    {
        return match ($mimeType) {
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif'],
            'image/webp' => ['webp'],
            'image/svg+xml' => ['svg'],
            'image/avif' => ['avif'],
            default => [],
        };
    }

    private function extensionFromMimeType(string $mimeType): ?string
    {
        $extensions = $this->extensionsForMimeType($mimeType);
        if (empty($extensions)) {
            return null;
        }

        return $extensions[0];
    }

    private function normalizeMimeType(string $mimeType): string
    {
        $mimeType = strtolower(trim($mimeType));
        if ($mimeType === 'image/x-webp') {
            return 'image/webp';
        }

        return $mimeType;
    }

    private function normalizeTargetDirectory(?string $targetDirectory): string
    {
        $normalized = trim(str_replace('\\', '/', (string) $targetDirectory), '/');

        if ($normalized === '') {
            return 'assets/images/upload';
        }

        if (str_contains($normalized, '..')) {
            throw new \InvalidArgumentException('Invalid target directory.');
        }

        return $normalized;
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
            $mimeType = Storage::disk('sites')->mimeType($filePath) ?: '';
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
                $width = is_array($imageSize) ? ($imageSize[0] ?? null) : null;
                $height = is_array($imageSize) ? ($imageSize[1] ?? null) : null;
                
                $this->repository->create([
                    'site_id' => $site->id,
                    'path' => $filePath,
                    'alt' => basename($filePath),
                    'width' => $width,
                    'height' => $height,
                    'size' => Storage::disk('sites')->size($filePath),
                    'mime_type' => $mimeType,
                ]);
                
                $discovered++;
            }
        }

        return $discovered;
    }
}
