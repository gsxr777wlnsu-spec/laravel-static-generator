<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminMediaServeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $sitesRoot = '/tmp/laravel-static-generator-tests/sites-' . Str::uuid();
        $generatedRoot = '/tmp/laravel-static-generator-tests/generated-' . Str::uuid();
        File::ensureDirectoryExists($sitesRoot);
        File::ensureDirectoryExists($generatedRoot);

        config()->set('filesystems.disks.sites.root', $sitesRoot);
        config()->set('filesystems.disks.generated.root', $generatedRoot);
        Storage::forgetDisk('sites');
        Storage::forgetDisk('generated');
    }

    public function test_media_serve_uses_database_mime_for_sites_disk_file(): void
    {
        $admin = User::factory()->create();
        $site = $this->createSite('mime-site.example');

        $path = "{$site->id}/assets/images/logo/logo.jpg";
        $content = 'mock-image-content';
        Storage::disk('sites')->put($path, $content);

        Media::create([
            'site_id' => $site->id,
            'path' => $path,
            'alt' => 'Logo',
            'mime_type' => 'image/webp',
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/sites/{$site->id}/media/serve/assets/images/logo/logo.jpg");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/webp');
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $response->assertContent($content);
    }

    public function test_media_serve_falls_back_to_generated_site_assets(): void
    {
        $admin = User::factory()->create();
        $site = $this->createSite('generated-fallback.example');

        $generatedPath = "site{$site->id}/assets/images/logo/logo.webp";
        $content = 'generated-site-logo';
        Storage::disk('generated')->put($generatedPath, $content);

        $response = $this->actingAs($admin)
            ->get("/admin/sites/{$site->id}/media/serve/assets/images/logo/logo.webp");

        $response->assertOk();
        $response->assertContent($content);
    }

    public function test_media_serve_falls_back_to_default_generated_site_assets(): void
    {
        $admin = User::factory()->create();
        $site = $this->createSite('default-fallback.example');

        $defaultPath = 'site1/assets/images/logo/logo.webp';
        $content = 'generated-default-logo';
        Storage::disk('generated')->put($defaultPath, $content);

        $response = $this->actingAs($admin)
            ->get("/admin/sites/{$site->id}/media/serve/assets/images/logo/logo.webp");

        $response->assertOk();
        $response->assertContent($content);
    }

    public function test_media_serve_rejects_path_traversal_attempts(): void
    {
        $admin = User::factory()->create();
        $site = $this->createSite('security-site.example');

        $response = $this->actingAs($admin)
            ->get("/admin/sites/{$site->id}/media/serve/..%2F..%2F.env");

        $response->assertNotFound();
    }

    private function createSite(string $domain): Site
    {
        return Site::create([
            'name' => 'Media Serve Site',
            'domain' => $domain,
            'template_set' => 'base',
            'output_path' => 'generated/media-serve-site',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);
    }
}
