<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\SiteRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function __construct(
        private SiteRepositoryInterface $sites
    ) {}

    public function index(int $siteId)
    {
        $site = $this->sites->findById($siteId);

        if (!$site) {
            return redirect()->route('admin.sites.index')
                ->with('error', 'Site not found');
        }

        $media = Media::where('site_id', $siteId)
            ->orderByDesc('id')
            ->get();

        return view('admin.media.index', compact('site', 'media'));
    }

    public function serve(int $siteId, string $path)
    {
        $site = $this->sites->findById($siteId);
        if (!$site) {
            abort(404);
        }

        [$disk, $fullPath] = $this->resolveAssetPath($siteId, (string) ($site->output_path ?? ''), $path);
        if ($disk === null || $fullPath === null) {
            abort(404);
        }

        $content = Storage::disk($disk)->get($fullPath);
        $mimeType = $this->resolveMimeType($siteId, $disk, $fullPath);

        return response($content, 200, [
            'Content-Type' => $mimeType,
            'X-Robots-Tag' => 'noindex, nofollow',
            'Cache-Control' => 'max-age=86400, private',
        ]);
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveAssetPath(int $siteId, string $outputPath, string $path): array
    {
        $normalizedPath = trim(str_replace('\\', '/', $path), '/');
        if ($normalizedPath === '' || str_contains($normalizedPath, '..')) {
            return [null, null];
        }

        $candidates = [
            ['sites', "{$siteId}/{$normalizedPath}"],
            ['generated', "site{$siteId}/{$normalizedPath}"],
            ['generated', "{$siteId}/{$normalizedPath}"],
            ['generated', "site1/{$normalizedPath}"],
            ['generated', "1/{$normalizedPath}"],
        ];

        $normalizedOutputPath = $this->normalizeGeneratedOutputPath($outputPath);
        if ($normalizedOutputPath !== null) {
            array_splice($candidates, 3, 0, [['generated', "{$normalizedOutputPath}/{$normalizedPath}"]]);
        }

        foreach ($candidates as [$disk, $candidatePath]) {
            if (Storage::disk($disk)->exists($candidatePath)) {
                return [$disk, $candidatePath];
            }
        }

        return [null, null];
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

    private function resolveMimeType(int $siteId, string $disk, string $fullPath): string
    {
        $mimeType = null;
        $extensionMimeType = $this->mimeTypeFromExtension($fullPath);

        if ($disk === 'sites') {
            $mimeType = Media::query()
                ->where('site_id', $siteId)
                ->where('path', $fullPath)
                ->value('mime_type');
        }

        if (!is_string($mimeType) || trim($mimeType) === '') {
            $mimeType = Storage::disk($disk)->mimeType($fullPath);
        }

        $mimeType = strtolower(trim((string) $mimeType));

        if ($mimeType === 'image/x-webp') {
            $mimeType = 'image/webp';
        }

        if (in_array($extensionMimeType, ['image/webp', 'image/svg+xml', 'image/avif'], true)) {
            return $extensionMimeType;
        }

        if (in_array($mimeType, ['text/plain', 'text/xml', 'application/xml', 'application/octet-stream'], true)) {
            $mimeType = $extensionMimeType ?? $mimeType;
        }

        if ($mimeType === '') {
            return $extensionMimeType ?? 'application/octet-stream';
        }

        return $mimeType;
    }

    private function mimeTypeFromExtension(string $path): ?string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'avif' => 'image/avif',
            'ico' => 'image/x-icon',
            default => null,
        };
    }
}
