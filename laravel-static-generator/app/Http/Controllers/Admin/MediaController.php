<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\SiteRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\Media;

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

        $fullPath = "{$siteId}/{$path}";

        if (!\Illuminate\Support\Facades\Storage::disk('sites')->exists($fullPath)) {
            abort(404);
        }

        $content = \Illuminate\Support\Facades\Storage::disk('sites')->get($fullPath);
        $mimeType = \Illuminate\Support\Facades\Storage::disk('sites')->mimeType($fullPath);

        return response($content, 200, [
            'Content-Type' => $mimeType,
            'X-Robots-Tag' => 'noindex, nofollow',
            'Cache-Control' => 'max-age=86400, private',
        ]);
    }
}
