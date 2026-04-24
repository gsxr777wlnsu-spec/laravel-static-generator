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

        [$disk, $fullPath] = $this->resolveAssetPath($siteId, $path);
        if ($disk === null || $fullPath === null) {
            abort(404);
        }

        $content = Storage::disk($disk)->get($fullPath);
        $mimeType = null;

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
        if ($mimeType === '') {
            $mimeType = 'application/octet-stream';
        }

        return response($content, 200, [
            'Content-Type' => $mimeType,
            'X-Robots-Tag' => 'noindex, nofollow',
            'Cache-Control' => 'max-age=86400, private',
        ]);
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveAssetPath(int $siteId, string $path): array
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

        foreach ($candidates as [$disk, $candidatePath]) {
            if (Storage::disk($disk)->exists($candidatePath)) {
                return [$disk, $candidatePath];
            }
        }

        return [null, null];
    }
}
