<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuditLogServiceInterface;
use App\Contracts\ImageProcessorInterface;
use App\Contracts\MediaManagerInterface;
use App\Contracts\MediaRepositoryInterface;
use App\Contracts\SiteRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MediaController extends Controller
{
    public function __construct(
        private MediaManagerInterface $manager,
        private MediaRepositoryInterface $media,
        private SiteRepositoryInterface $sites,
        private ImageProcessorInterface $processor,
        private AuditLogServiceInterface $audit
    ) {}

    public function index(Request $request): JsonResponse
    {
        $siteId = $request->query('site_id');
        $directory = $request->query('directory');
        
        if ($siteId) {
            $site = $this->sites->findById($siteId);
            if (!$site) {
                 return response()->json(['error' => 'Site not found'], 404);
             }

            if (is_string($directory) && trim($directory) !== '') {
                return response()->json($this->listDirectoryPayload($siteId, (string) ($site->output_path ?? ''), $directory));
            }

            // Trigger discovery to sync database with filesystem
            $this->manager->discoverExistingMedia($site);
            
            $media = $this->media->getBySite($site);
        } else {
            $media = Media::all();
        }

        return response()->json($media);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'site_id' => 'required|exists:sites,id',
            // Do not use `image|mimes` here: some TinyMCE WebP blobs fail extension guessing.
            // MediaManagerService performs strict MIME validation.
            'file' => 'required|file|max:10240',
            'alt' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'target_directory' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $site = $this->sites->findById($request->site_id);
        
        try {
            $media = $this->manager->upload(
                $request->file('file'),
                $site,
                $request->alt,
                $request->title,
                $request->input('target_directory')
            );
            
            $this->audit->log('media.uploaded', Media::class, $media->id, null, $media->toArray());

            return response()->json($media, 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $media = Media::find($id);

        if (!$media) {
            return response()->json(['error' => 'Media not found'], 404);
        }

        return response()->json($media);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $media = Media::find($id);

        if (!$media) {
            return response()->json(['error' => 'Media not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'alt' => 'sometimes|string|max:255',
            'title' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldValues = $media->toArray();
        $media = $this->media->update($media, $validator->validated());
        
        $this->audit->log('media.updated', Media::class, $media->id, $oldValues, $media->toArray());

        return response()->json($media);
    }

    public function destroy(int $id): JsonResponse
    {
        $media = Media::find($id);

        if (!$media) {
            return response()->json(['error' => 'Media not found'], 404);
        }

        $this->audit->log('media.deleted', Media::class, $media->id, $media->toArray(), null);
        
        $this->manager->delete($media);

        return response()->json(['message' => 'Media deleted successfully']);
    }

    public function destroySiteFile(Request $request): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'site_id' => 'required|integer|exists:sites,id',
            'path' => 'required|string|max:1000',
        ])->validate();

        $relativePath = $this->normalizeRelativeDirectory($validated['path']);
        if ($relativePath === null || !str_starts_with($relativePath, 'assets/')) {
            return response()->json(['error' => 'Invalid site media path'], 422);
        }

        $sitePath = $validated['site_id'] . '/' . $relativePath;
        if (!Storage::disk('sites')->exists($sitePath)) {
            return response()->json(['error' => 'Site media file not found'], 404);
        }

        $record = Media::query()
            ->where('site_id', $validated['site_id'])
            ->where('path', $sitePath)
            ->first();

        if ($record) {
            $this->audit->log('media.deleted', Media::class, $record->id, $record->toArray(), null);
            $this->manager->delete($record);
        } else {
            Storage::disk('sites')->delete($sitePath);
        }

        return response()->json(['message' => 'Media deleted successfully']);
    }

    public function resize(Request $request, int $id): JsonResponse
    {
        $media = Media::find($id);

        if (!$media) {
            return response()->json(['error' => 'Media not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'width' => 'required|integer|min:1',
            'height' => 'required|integer|min:1',
            'preserve_aspect_ratio' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $newMedia = $this->processor->resize(
                $media,
                $request->width,
                $request->height,
                $request->preserve_aspect_ratio ?? true
            );

            return response()->json($newMedia, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @return array{
     *   files: array<int, array<string, mixed>>,
     *   directories: array<int, string>,
     *   current_directory: string,
     *   parent_directory: string|null
     * }
     */
    private function listDirectoryPayload(int $siteId, string $outputPath, string $directory): array
    {
        $normalizedDirectory = $this->normalizeRelativeDirectory($directory);
        if ($normalizedDirectory === null) {
            return [
                'files' => [],
                'directories' => [],
                'current_directory' => '',
                'parent_directory' => null,
            ];
        }

        $directories = $this->resolveDirectoryPaths($siteId, $outputPath, $normalizedDirectory);
        $recordsByPath = Media::query()
            ->where('site_id', $siteId)
            ->get()
            ->keyBy('path');

        $files = [];
        $seenRelativePaths = [];

        foreach ($directories as [$disk, $fullDirectory]) {
            try {
                $directoryFiles = Storage::disk($disk)->files($fullDirectory);
            } catch (\Throwable) {
                continue;
            }

            foreach ($directoryFiles as $filePath) {
                if (!$this->isSupportedMediaFile($disk, $filePath)) {
                    continue;
                }

                $relativePath = $this->relativeAssetPath($siteId, $filePath);
                if ($relativePath === null || isset($seenRelativePaths[$relativePath])) {
                    continue;
                }

                $seenRelativePaths[$relativePath] = true;
                $record = $recordsByPath->get($filePath);

                $files[] = [
                    'id' => $record?->id,
                    'site_id' => $siteId,
                    'path' => $relativePath,
                    'alt' => $record?->alt ?? pathinfo($filePath, PATHINFO_FILENAME),
                    'title' => $record?->title,
                    'mime_type' => $record?->mime_type ?? (Storage::disk($disk)->mimeType($filePath) ?: null),
                    'url' => route('admin.media.serve', ['siteId' => $siteId, 'path' => $relativePath]),
                    'created_at' => $record?->created_at?->toJSON(),
                    'deletable' => $disk === 'sites' && $filePath === "{$siteId}/{$relativePath}",
                ];
            }
        }

        return [
            'files' => $files,
            'directories' => $this->collectAvailableDirectories($siteId, $outputPath),
            'current_directory' => $normalizedDirectory,
            'parent_directory' => $this->parentDirectory($normalizedDirectory),
        ];
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function resolveDirectoryPaths(int $siteId, string $outputPath, string $directory): array
    {
        $candidates = [
            ['sites', "{$siteId}/{$directory}"],
            ['generated', "site{$siteId}/{$directory}"],
            ['generated', "{$siteId}/{$directory}"],
            ['generated', "site1/{$directory}"],
            ['generated', "1/{$directory}"],
        ];

        $normalizedOutputPath = $this->normalizeGeneratedOutputPath($outputPath);
        if ($normalizedOutputPath !== null) {
            array_splice($candidates, 3, 0, [['generated', "{$normalizedOutputPath}/{$directory}"]]);
        }

        $resolved = [];
        $seen = [];

        foreach ($candidates as [$disk, $candidatePath]) {
            try {
                $exists = Storage::disk($disk)->exists($candidatePath);
            } catch (\Throwable) {
                $exists = false;
            }

            if ($exists) {
                $key = "{$disk}:{$candidatePath}";
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $resolved[] = [$disk, $candidatePath];
                }
            }
        }

        return $resolved;
    }

    private function normalizeRelativeDirectory(string $directory): ?string
    {
        $normalized = trim(str_replace('\\', '/', $directory), '/');
        if ($normalized === '' || str_contains($normalized, '..')) {
            return null;
        }

        return $normalized;
    }

    private function normalizeGeneratedOutputPath(string $outputPath): ?string
    {
        $normalized = trim(str_replace('\\', '/', $outputPath), '/');
        if ($normalized === '' || str_contains($normalized, '..')) {
            return null;
        }

        if (str_starts_with($normalized, 'generated/')) {
            $normalized = substr($normalized, strlen('generated/'));
        }

        return $normalized === '' ? null : $normalized;
    }

    private function isSupportedMediaFile(string $disk, string $path): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'], true)) {
            return false;
        }

        $mimeType = strtolower((string) (Storage::disk($disk)->mimeType($path) ?: ''));
        if ($mimeType === 'image/x-webp') {
            $mimeType = 'image/webp';
        }

        return match ($extension) {
            'jpg', 'jpeg' => $mimeType === 'image/jpeg',
            'png' => $mimeType === 'image/png',
            'gif' => $mimeType === 'image/gif',
            'webp' => $mimeType === 'image/webp',
            'svg' => in_array($mimeType, ['image/svg+xml', 'text/plain', 'text/xml', 'application/xml'], true),
            'avif' => $mimeType === 'image/avif',
            default => false,
        };
    }

    /**
     * @return array<int, string>
     */
    private function collectAvailableDirectories(int $siteId, string $outputPath): array
    {
        $candidateRoots = $this->resolveDirectoryPaths($siteId, $outputPath, 'assets');
        $directories = [
            'assets/images',
            'assets/svg',
        ];
        $seen = array_fill_keys($directories, true);

        foreach ($candidateRoots as [$disk, $rootPath]) {
            try {
                $allDirectories = Storage::disk($disk)->allDirectories($rootPath);
            } catch (\Throwable) {
                $allDirectories = [];
            }

            foreach ($allDirectories as $directoryPath) {
                $relativePath = $this->relativeAssetPath($siteId, $directoryPath);
                if ($relativePath === null) {
                    continue;
                }

                if (
                    !str_starts_with($relativePath, 'assets/images')
                    && !str_starts_with($relativePath, 'assets/svg')
                ) {
                    continue;
                }

                if (!isset($seen[$relativePath])) {
                    $seen[$relativePath] = true;
                    $directories[] = $relativePath;
                }
            }
        }

        sort($directories);

        return $directories;
    }

    private function relativeAssetPath(int $siteId, string $path): ?string
    {
        $normalized = str_replace('\\', '/', $path);

        foreach ([
            "{$siteId}/",
            "site{$siteId}/",
            'site1/',
            '1/',
            'preview/',
        ] as $prefix) {
            if (str_starts_with($normalized, $prefix) && str_contains($normalized, '/assets/')) {
                $assetPosition = strpos($normalized, 'assets/');
                return $assetPosition === false ? null : substr($normalized, $assetPosition);
            }
        }

        if (str_contains($normalized, '/assets/')) {
            $assetPosition = strpos($normalized, 'assets/');
            return $assetPosition === false ? null : substr($normalized, $assetPosition);
        }

        return null;
    }

    private function parentDirectory(string $directory): ?string
    {
        $directory = trim($directory, '/');
        if ($directory === '' || $directory === 'assets' || $directory === 'assets/images' || $directory === 'assets/svg') {
            return null;
        }

        $parent = dirname($directory);
        return $parent === '.' ? null : str_replace('\\', '/', $parent);
    }
}
