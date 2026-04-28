<?php

namespace Tests\Feature;

use App\Contracts\SftpClientInterface;
use App\Contracts\HtmlGeneratorInterface;
use App\Models\AuditLog;
use App\Models\Page;
use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PageDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-page-delete@test.com',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_destroy_removes_remote_page_and_local_artifacts_without_db_tails(): void
    {
        $this->useTemporaryStorageRoots();

        $site = Site::create([
            'name' => 'Page Delete Site',
            'domain' => 'page-delete.example',
            'template_set' => 'base',
            'output_path' => 'generated/page-delete',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
            'sftp_host' => '37.1.217.183',
            'sftp_port' => 22,
            'sftp_username' => 'root',
            'sftp_password' => encrypt('secret'),
            'sftp_auth_method' => 'password',
            'sftp_remote_path' => '/var/www/html/page-delete.example',
        ]);

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'tips',
            'title' => 'Tips',
            'status' => 'published',
            'locale' => 'en',
            'template_key' => 'tips',
        ]);

        $section = Section::create([
            'page_id' => $page->id,
            'type' => 'module',
            'content' => ['module' => 'tips'],
            'order' => 0,
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

        Storage::disk('generated')->put("site{$site->id}/tips.html", '<html>tips</html>');
        Storage::disk('generated')->put('page-delete/tips.html', '<html>tips</html>');
        Storage::disk('staging')->put("site{$site->id}/tips.html", '<html>tips</html>');

        $mockSftp = \Mockery::mock(SftpClientInterface::class);
        $mockSftp->shouldReceive('connect')->once()->withArgs(function (Site $argSite) use ($site) {
            return $argSite->id === $site->id;
        })->andReturn(true);
        $mockSftp->shouldReceive('deleteFile')->once()->withArgs(function (Site $argSite, string $remoteFilePath) use ($site) {
            return $argSite->id === $site->id
                && $remoteFilePath === 'var/www/html/page-delete.example/tips.html';
        })->andReturn(true);
        $mockSftp->shouldReceive('disconnect')->once();

        $this->app->instance(SftpClientInterface::class, $mockSftp);

        $response = $this->actingAs($this->admin)->deleteJson("/api/pages/{$page->id}");
        $response->assertOk();

        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
        $this->assertDatabaseMissing('audit_logs', ['auditable_type' => Page::class, 'auditable_id' => $page->id]);
        $this->assertDatabaseMissing('audit_logs', ['auditable_type' => Section::class, 'auditable_id' => $section->id]);

        $this->assertFalse(Storage::disk('generated')->exists("site{$site->id}/tips.html"));
        $this->assertFalse(Storage::disk('generated')->exists('page-delete/tips.html'));
        $this->assertFalse(Storage::disk('staging')->exists("site{$site->id}/tips.html"));
    }

    public function test_destroy_returns_error_and_keeps_page_when_remote_delete_fails(): void
    {
        $site = Site::create([
            'name' => 'Page Delete Fail Site',
            'domain' => 'page-delete-fail.example',
            'template_set' => 'base',
            'output_path' => 'generated/page-delete-fail',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
            'sftp_host' => '37.1.217.183',
            'sftp_port' => 22,
            'sftp_username' => 'root',
            'sftp_password' => encrypt('secret'),
            'sftp_auth_method' => 'password',
            'sftp_remote_path' => '/var/www/html/page-delete-fail.example',
        ]);

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'demo',
            'title' => 'Demo',
            'status' => 'published',
            'locale' => 'en',
            'template_key' => 'demo',
        ]);

        $mockSftp = \Mockery::mock(SftpClientInterface::class);
        $mockSftp->shouldReceive('connect')->once()->andReturn(true);
        $mockSftp->shouldReceive('deleteFile')->once()->andReturn(false);
        $mockSftp->shouldReceive('disconnect')->once();

        $this->app->instance(SftpClientInterface::class, $mockSftp);

        $response = $this->actingAs($this->admin)->deleteJson("/api/pages/{$page->id}");
        $response->assertStatus(422);

        $this->assertDatabaseHas('pages', ['id' => $page->id]);
    }

    public function test_store_auto_uploads_sitemap_html_and_xml_when_sitemap_page_exists(): void
    {
        $this->useTemporaryStorageRoots();

        $site = Site::create([
            'name' => 'Page Create Site',
            'domain' => 'page-create.example',
            'template_set' => 'base',
            'output_path' => 'generated/page-create',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
            'sftp_host' => '37.1.217.183',
            'sftp_port' => 22,
            'sftp_username' => 'root',
            'sftp_password' => encrypt('secret'),
            'sftp_auth_method' => 'password',
            'sftp_remote_path' => '/var/www/html/page-create.example',
        ]);

        $sitemapPage = Page::create([
            'site_id' => $site->id,
            'slug' => 'sitemap',
            'title' => 'Sitemap',
            'status' => 'published',
            'locale' => 'en',
            'template_key' => 'sitemap',
        ]);

        $mockGenerator = \Mockery::mock(HtmlGeneratorInterface::class);
        $mockGenerator->shouldReceive('generateSitemap')->once()->withArgs(function (Site $argSite) use ($site) {
            return $argSite->id === $site->id;
        })->andReturn('<xml>ok</xml>');
        $mockGenerator->shouldReceive('generatePage')->once()->withArgs(function (Page $argPage) use ($sitemapPage) {
            return $argPage->id === $sitemapPage->id;
        })->andReturn('<html>sitemap</html>');
        $this->app->instance(HtmlGeneratorInterface::class, $mockGenerator);

        $mockSftp = \Mockery::mock(SftpClientInterface::class);
        $mockSftp->shouldReceive('connect')->once()->withArgs(function (Site $argSite) use ($site) {
            return $argSite->id === $site->id;
        })->andReturn(true);
        $mockSftp->shouldReceive('uploadFile')->twice()->andReturnUsing(function (Site $argSite, string $localFilePath, string $remoteFilePath) use ($site) {
            if ($argSite->id !== $site->id) {
                return false;
            }

            return in_array([$localFilePath, $remoteFilePath], [
                ["site{$site->id}/sitemap.xml", 'var/www/html/page-create.example/sitemap.xml'],
                ["site{$site->id}/sitemap.html", 'var/www/html/page-create.example/sitemap.html'],
            ], true);
        });
        $mockSftp->shouldReceive('disconnect')->once();
        $this->app->instance(SftpClientInterface::class, $mockSftp);

        $response = $this->actingAs($this->admin)->postJson('/api/pages', [
            'site_id' => $site->id,
            'slug' => 'faq',
            'title' => 'FAQ',
            'status' => 'published',
            'locale' => 'en',
            'template_key' => 'blank',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('pages', ['site_id' => $site->id, 'slug' => 'faq']);
    }

    public function test_destroy_auto_uploads_sitemap_html_and_xml_when_sitemap_page_exists(): void
    {
        $this->useTemporaryStorageRoots();

        $site = Site::create([
            'name' => 'Page Delete With Sitemap Site',
            'domain' => 'page-delete-sitemap.example',
            'template_set' => 'base',
            'output_path' => 'generated/page-delete-sitemap',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
            'sftp_host' => '37.1.217.183',
            'sftp_port' => 22,
            'sftp_username' => 'root',
            'sftp_password' => encrypt('secret'),
            'sftp_auth_method' => 'password',
            'sftp_remote_path' => '/var/www/html/page-delete-sitemap.example',
        ]);

        $sitemapPage = Page::create([
            'site_id' => $site->id,
            'slug' => 'sitemap',
            'title' => 'Sitemap',
            'status' => 'published',
            'locale' => 'en',
            'template_key' => 'sitemap',
        ]);

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'bonus',
            'title' => 'Bonus',
            'status' => 'published',
            'locale' => 'en',
            'template_key' => 'bonuses',
        ]);

        $mockGenerator = \Mockery::mock(HtmlGeneratorInterface::class);
        $mockGenerator->shouldReceive('generateSitemap')->once()->withArgs(function (Site $argSite) use ($site) {
            return $argSite->id === $site->id;
        })->andReturn('<xml>ok</xml>');
        $mockGenerator->shouldReceive('generatePage')->once()->withArgs(function (Page $argPage) use ($sitemapPage) {
            return $argPage->id === $sitemapPage->id;
        })->andReturn('<html>sitemap</html>');
        $this->app->instance(HtmlGeneratorInterface::class, $mockGenerator);

        $mockSftp = \Mockery::mock(SftpClientInterface::class);
        $mockSftp->shouldReceive('connect')->twice()->withArgs(function (Site $argSite) use ($site) {
            return $argSite->id === $site->id;
        })->andReturn(true);
        $mockSftp->shouldReceive('deleteFile')->once()->withArgs(function (Site $argSite, string $remoteFilePath) use ($site) {
            return $argSite->id === $site->id
                && $remoteFilePath === 'var/www/html/page-delete-sitemap.example/bonus.html';
        })->andReturn(true);
        $mockSftp->shouldReceive('uploadFile')->twice()->andReturnUsing(function (Site $argSite, string $localFilePath, string $remoteFilePath) use ($site) {
            if ($argSite->id !== $site->id) {
                return false;
            }

            return in_array([$localFilePath, $remoteFilePath], [
                ["site{$site->id}/sitemap.xml", 'var/www/html/page-delete-sitemap.example/sitemap.xml'],
                ["site{$site->id}/sitemap.html", 'var/www/html/page-delete-sitemap.example/sitemap.html'],
            ], true);
        });
        $mockSftp->shouldReceive('disconnect')->twice();
        $this->app->instance(SftpClientInterface::class, $mockSftp);

        $response = $this->actingAs($this->admin)->deleteJson("/api/pages/{$page->id}");
        $response->assertOk();
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    private function useTemporaryStorageRoots(): void
    {
        $basePath = '/tmp/laravel-static-generator-tests/page-delete-' . Str::uuid();
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
