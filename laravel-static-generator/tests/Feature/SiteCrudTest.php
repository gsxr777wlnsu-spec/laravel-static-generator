<?php

namespace Tests\Feature;

use App\Contracts\SftpClientInterface;
use App\Models\AuditLog;
use App\Models\Deployment;
use App\Models\Media;
use App\Models\Page;
use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class SiteCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-site@test.com',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_store_persists_status_and_locale_fields(): void
    {
        $payload = [
            'name' => 'Landing RU',
            'domain' => 'landing-ru.example',
            'template_set' => 'base',
            'output_path' => 'generated/landing-ru',
            'status' => 'active',
            'locale' => 'ru',
            'default_locale' => 'ru',
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/sites', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'active');
        $response->assertJsonPath('locale', 'ru');
        $response->assertJsonPath('output_path', 'generated/landing-ru');

        $this->assertDatabaseHas('sites', [
            'domain' => 'landing-ru.example',
            'status' => 'active',
            'locale' => 'ru',
            'default_locale' => 'ru',
            'output_path' => 'generated/landing-ru',
        ]);
    }

    public function test_update_persists_output_path_locale_and_status(): void
    {
        $site = Site::create([
            'name' => 'Initial Site',
            'domain' => 'initial-site.example',
            'template_set' => 'base',
            'output_path' => 'generated/initial',
            'status' => 'draft',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);

        $response = $this->actingAs($this->admin)->putJson("/api/sites/{$site->id}", [
            'output_path' => 'generated/updated',
            'locale' => 'de',
            'default_locale' => 'de',
            'status' => 'inactive',
        ]);

        $response->assertOk();
        $response->assertJsonPath('output_path', 'generated/updated');
        $response->assertJsonPath('locale', 'de');
        $response->assertJsonPath('default_locale', 'de');
        $response->assertJsonPath('status', 'inactive');

        $this->assertDatabaseHas('sites', [
            'id' => $site->id,
            'output_path' => 'generated/updated',
            'locale' => 'de',
            'default_locale' => 'de',
            'status' => 'inactive',
        ]);
    }

    public function test_destroy_removes_site_related_data_and_artifacts(): void
    {
        $this->useTemporaryStorageRoots();

        $site = Site::create([
            'name' => 'Delete Site',
            'domain' => 'delete-site.example',
            'template_set' => 'base',
            'output_path' => 'generated/delete-site',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'index',
            'title' => 'Home',
            'status' => 'published',
            'locale' => 'en',
        ]);

        $section = Section::create([
            'page_id' => $page->id,
            'type' => 'text',
            'content' => ['text' => 'x'],
            'order' => 0,
        ]);

        $media = Media::create([
            'site_id' => $site->id,
            'path' => "{$site->id}/assets/images/upload/test.png",
            'alt' => 'test',
            'size' => 10,
            'mime_type' => 'image/png',
        ]);

        $deployment = Deployment::create([
            'site_id' => $site->id,
            'status' => 'completed',
        ]);

        AuditLog::create([
            'user_id' => 0,
            'action' => 'site.updated',
            'auditable_type' => Site::class,
            'auditable_id' => $site->id,
            'created_at' => now(),
        ]);
        AuditLog::create([
            'user_id' => 0,
            'action' => 'page.updated',
            'auditable_type' => Page::class,
            'auditable_id' => $page->id,
            'created_at' => now(),
        ]);
        AuditLog::create([
            'user_id' => 0,
            'action' => 'section.updated',
            'auditable_type' => Section::class,
            'auditable_id' => $section->id,
            'created_at' => now(),
        ]);
        AuditLog::create([
            'user_id' => 0,
            'action' => 'media.updated',
            'auditable_type' => Media::class,
            'auditable_id' => $media->id,
            'created_at' => now(),
        ]);
        AuditLog::create([
            'user_id' => 0,
            'action' => 'deployment.updated',
            'auditable_type' => Deployment::class,
            'auditable_id' => $deployment->id,
            'created_at' => now(),
        ]);

        Storage::disk('sites')->put("{$site->id}/assets/images/upload/test.png", 'binary');
        Storage::disk('generated')->put("site{$site->id}/index.html", '<html></html>');
        Storage::disk('generated')->put('delete-site/index.html', '<html></html>');
        Storage::disk('staging')->put("site{$site->id}/keep.txt", 'temp');

        $templatesRoot = '/tmp/laravel-static-generator-tests/ai-templates-delete-' . Str::uuid();
        $siteTemplateDir = $templatesRoot . '/delete-site.example';
        File::ensureDirectoryExists($siteTemplateDir);
        config()->set('services.ai_agent.templates_root', $templatesRoot);

        $templateYaml = Yaml::dump([
            'domain' => 'delete-site.example',
            'name' => 'delete-site.example',
            'template' => 'test',
            'output_path' => 'generated/delete-site.example',
            'status' => 'active',
            'locale' => 'en',
            'pages' => [],
        ], 8, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        File::put($siteTemplateDir . '/index-raw_html.md', "---\n" . $templateYaml);

        $response = $this->actingAs($this->admin)->deleteJson("/api/sites/{$site->id}");
        $response->assertOk();

        $this->assertDatabaseMissing('sites', ['id' => $site->id]);
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->assertDatabaseMissing('deployments', ['id' => $deployment->id]);
        $this->assertDatabaseMissing('audit_logs', ['auditable_type' => Site::class, 'auditable_id' => $site->id]);
        $this->assertDatabaseMissing('audit_logs', ['auditable_type' => Page::class, 'auditable_id' => $page->id]);
        $this->assertDatabaseMissing('audit_logs', ['auditable_type' => Section::class, 'auditable_id' => $section->id]);
        $this->assertDatabaseMissing('audit_logs', ['auditable_type' => Media::class, 'auditable_id' => $media->id]);
        $this->assertDatabaseMissing('audit_logs', ['auditable_type' => Deployment::class, 'auditable_id' => $deployment->id]);

        $this->assertFalse(Storage::disk('sites')->exists((string) $site->id));
        $this->assertFalse(Storage::disk('generated')->exists("site{$site->id}"));
        $this->assertFalse(Storage::disk('generated')->exists('delete-site'));
        $this->assertFalse(Storage::disk('staging')->exists("site{$site->id}"));
        $this->assertFalse(File::isDirectory($siteTemplateDir));
    }

    public function test_destroy_keeps_remote_directory_when_sftp_is_configured(): void
    {
        $this->useTemporaryStorageRoots();

        $site = Site::create([
            'name' => 'Remote Delete Site',
            'domain' => 'remote-delete.example',
            'template_set' => 'base',
            'output_path' => 'generated/remote-delete',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
            'sftp_host' => '37.1.217.183',
            'sftp_port' => 22,
            'sftp_username' => 'root',
            'sftp_password' => encrypt('secret'),
            'sftp_auth_method' => 'password',
            'sftp_remote_path' => '/var/www/html/remote-delete.example',
        ]);

        $mockSftp = \Mockery::mock(SftpClientInterface::class);
        $mockSftp->shouldNotReceive('connect');
        $mockSftp->shouldNotReceive('deleteDirectory');
        $mockSftp->shouldNotReceive('disconnect');

        $this->app->instance(SftpClientInterface::class, $mockSftp);

        $response = $this->actingAs($this->admin)->deleteJson("/api/sites/{$site->id}");
        $response->assertOk();

        $this->assertDatabaseMissing('sites', ['id' => $site->id]);
    }

    private function useTemporaryStorageRoots(): void
    {
        $basePath = '/tmp/laravel-static-generator-tests/site-delete-' . Str::uuid();
        $sitesRoot = $basePath . '/storage/sites';
        $generatedRoot = $basePath . '/storage/generated';
        $stagingRoot = $basePath . '/storage/staging';

        File::ensureDirectoryExists($sitesRoot);
        File::ensureDirectoryExists($generatedRoot);
        File::ensureDirectoryExists($stagingRoot);

        config()->set('filesystems.disks.sites.root', $sitesRoot);
        config()->set('filesystems.disks.generated.root', $generatedRoot);
        config()->set('filesystems.disks.staging.root', $stagingRoot);
        Storage::forgetDisk('sites');
        Storage::forgetDisk('generated');
        Storage::forgetDisk('staging');
    }
}
