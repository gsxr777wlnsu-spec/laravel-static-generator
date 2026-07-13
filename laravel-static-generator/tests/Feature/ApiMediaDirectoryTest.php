<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiMediaDirectoryTest extends TestCase
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

    public function test_media_index_lists_files_from_requested_directory_across_sources(): void
    {
        $admin = User::factory()->create();
        $site = Site::create([
            'name' => 'Directory Site',
            'domain' => 'directory-site.example',
            'template_set' => 'base',
            'output_path' => 'generated/directory-site.example',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);

        Storage::disk('generated')->put('directory-site.example/assets/svg/gift.svg', '<svg></svg>');
        Storage::disk('generated')->put('preview/token-1/assets/svg/diamond.svg', '<svg></svg>');

        $response = $this->actingAs($admin)->getJson('/api/media?site_id=' . $site->id . '&directory=assets/svg');

        $response->assertOk();
        $response->assertJsonFragment([
            'path' => 'assets/svg/gift.svg',
        ]);
        $response->assertJsonFragment([
            'path' => 'assets/svg/diamond.svg',
        ]);
    }

    public function test_media_store_uploads_into_requested_directory(): void
    {
        $admin = User::factory()->create();
        $site = Site::create([
            'name' => 'Upload Directory Site',
            'domain' => 'upload-directory.example',
            'template_set' => 'base',
            'output_path' => 'generated/upload-directory.example',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);

        $file = UploadedFile::fake()->create('logo.svg', 8, 'image/svg+xml');

        $response = $this->actingAs($admin)->post('/api/media', [
            'site_id' => $site->id,
            'file' => $file,
            'alt' => 'Logo',
            'target_directory' => 'assets/images/logo',
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertCreated();
        $path = (string) $response->json('path');
        $this->assertStringStartsWith($site->id . '/assets/images/logo/', $path);
        $this->assertStringEndsWith('.svg', $path);
        Storage::disk('sites')->assertExists($path);
    }

    public function test_site_media_file_can_be_deleted_with_its_database_record(): void
    {
        $admin = User::factory()->create();
        $site = $this->createSite('delete-site.example');
        $path = "{$site->id}/assets/images/upload/photo.png";
        Storage::disk('sites')->put($path, 'site-photo');
        $media = Media::create([
            'site_id' => $site->id,
            'path' => $path,
            'alt' => 'Photo',
            'mime_type' => 'image/png',
            'size' => 10,
        ]);

        $response = $this->actingAs($admin)->deleteJson('/api/media/file', [
            'site_id' => $site->id,
            'path' => 'assets/images/upload/photo.png',
        ]);

        $response->assertOk();
        Storage::disk('sites')->assertMissing($path);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_template_file_cannot_be_deleted_through_site_media_endpoint(): void
    {
        $admin = User::factory()->create();
        $site = $this->createSite('protected-base.example');
        Storage::disk('generated')->put('site1/assets/images/base.png', 'base-photo');

        $response = $this->actingAs($admin)->deleteJson('/api/media/file', [
            'site_id' => $site->id,
            'path' => 'assets/images/base.png',
        ]);

        $response->assertNotFound();
        Storage::disk('generated')->assertExists('site1/assets/images/base.png');
    }

    private function createSite(string $domain): Site
    {
        return Site::create([
            'name' => $domain,
            'domain' => $domain,
            'template_set' => 'base',
            'output_path' => "generated/{$domain}",
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);
    }
}
