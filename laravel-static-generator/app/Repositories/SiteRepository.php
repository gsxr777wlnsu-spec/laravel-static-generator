<?php

namespace App\Repositories;

use App\Contracts\SiteRepositoryInterface;
use App\Models\Site;
use App\Models\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SiteRepository implements SiteRepositoryInterface
{
    public function create(array $data): Site
    {
        $site = Site::create($data);
        $this->initializeSiteDirectoryStructure($site);
        return $site;
    }

    public function update(Site $site, array $data): Site
    {
        $site->update($data);
        return $site->fresh();
    }

    public function delete(Site $site): bool
    {
        return $site->delete();
    }

    public function findById(int $id): ?Site
    {
        return Site::find($id);
    }

    public function findByDomain(string $domain): ?Site
    {
        return Site::where('domain', $domain)->first();
    }

    public function getActive(): Collection
    {
        return Site::where('status', 'active')->get();
    }

    public function getAll(): Collection
    {
        return Site::all();
    }

    public function clone(Site $site, array $overrides): Site
    {
        $newSite = $this->create(array_merge(
            $site->only(['name', 'template_set', 'locale', 'default_locale']),
            $overrides
        ));

        // Note: create() already called initializeSiteDirectoryStructure

        foreach ($site->pages as $page) {
            $newPage = $newSite->pages()->create($page->only([
                'slug', 'title', 'meta_title', 'meta_description', 
                'meta_keywords', 'canonical', 'og_data', 'json_ld', 
                'status', 'locale'
            ]));

            foreach ($page->sections as $section) {
                $newPage->sections()->create($section->only(['type', 'content', 'order']));
            }
        }

        return $newSite;
    }

    private function initializeSiteDirectoryStructure(Site $site): void
    {
        $directories = [
            "{$site->id}/assets/images/logo",
            "{$site->id}/assets/images/upload",
            "{$site->id}/assets/css",
            "{$site->id}/assets/js",
        ];

        foreach ($directories as $dir) {
            if (!Storage::disk('sites')->exists($dir)) {
                Storage::disk('sites')->makeDirectory($dir);
            }
        }
    }

    public function cloneFromStaging(string $stagingPath, array $siteData): ?Site
    {
        if (!File::exists($stagingPath)) {
            throw new \Exception("Staging path does not exist: {$stagingPath}");
        }

        $files = Storage::disk('generated')->files($stagingPath);
        
        if (empty($files)) {
            throw new \Exception("No HTML files found in staging path: {$stagingPath}");
        }

        $newSite = $this->create([
            'name' => $siteData['name'],
            'domain' => $siteData['domain'],
            'template_set' => $siteData['template_set'] ?? 'base',
            'output_path' => $siteData['output_path'] ?? "generated/{$siteData['domain']}",
            'status' => 'draft',
            'locale' => $siteData['locale'] ?? 'en',
        ]);

        $htmlFiles = array_filter($files, function ($file) {
            return Str::endsWith($file, '.html');
        });

        foreach ($htmlFiles as $file) {
            $slug = basename($file, '.html');
            $slug = $slug === 'index' ? '' : $slug;

            $newSite->pages()->create([
                'slug' => $slug,
                'title' => Str::title(str_replace('-', ' ', $slug)),
                'status' => 'published',
            ]);
        }

        return $newSite;
    }
}
