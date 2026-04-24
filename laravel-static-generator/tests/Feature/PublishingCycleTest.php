<?php

namespace Tests\Feature;

use App\Contracts\SftpClientInterface;
use App\Models\Page;
use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use App\Services\GitService;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublishingCycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-cycle@test.com',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_generate_then_preview_cycle_works(): void
    {
        $this->useTemporaryGeneratedDisk();

        $site = Site::create([
            'name' => 'Cycle Site',
            'domain' => 'cycle.example',
            'template_set' => 'base',
            'output_path' => 'generated/cycle',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'index',
            'title' => 'Home Page',
            'status' => 'published',
            'locale' => 'en',
        ]);

        Section::create([
            'page_id' => $page->id,
            'type' => 'text',
            'order' => 1,
            'content' => [
                'heading' => 'Welcome',
                'content' => '<p>Generated content</p>',
                'module' => 'hero-main',
                'id' => 'hero-main',
                'class' => 'hero-section reusable-block',
            ],
        ]);

        $mockGitService = \Mockery::mock(GitService::class);
        $mockGitService->shouldReceive('setRepositoryPath')->andReturnSelf();
        $mockGitService->shouldReceive('commit')->andReturnNull();
        $this->app->instance(GitService::class, $mockGitService);

        $generateResponse = $this->actingAs($this->admin)->postJson("/api/sites/{$site->id}/generate");
        $generateResponse->assertOk();
        $this->assertTrue(
            (bool) $generateResponse->json('success'),
            'Generation failed: '.json_encode($generateResponse->json(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        Storage::disk('generated')->assertExists("site{$site->id}/index.html");
        Storage::disk('generated')->assertExists("site{$site->id}/sitemap.xml");
        Storage::disk('generated')->assertExists("site{$site->id}/robots.txt");

        $previewTokenResponse = $this->actingAs($this->admin)->postJson("/api/pages/{$page->id}/preview-token");
        $previewTokenResponse->assertOk();
        $previewTokenResponse->assertJsonStructure(['preview_url', 'expires_at']);

        $previewUrl = $previewTokenResponse->json('preview_url');
        $this->assertIsString($previewUrl);
        $this->assertStringStartsWith('/api/preview/', $previewUrl);
        $previewResponse = $this->actingAs($this->admin)->get($previewUrl);

        $previewResponse->assertOk();
        $previewResponse->assertSee('Home Page');
        $previewResponse->assertSee('Welcome');
    }

    public function test_deploy_uses_generated_directory_and_completes(): void
    {
        $this->useTemporaryGeneratedDisk();

        $site = Site::create([
            'name' => 'Deploy Site',
            'domain' => 'deploy.example',
            'template_set' => 'base',
            'output_path' => 'generated/deploy',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
            'sftp_host' => 'sftp.example.com',
            'sftp_username' => 'deploy-user',
            'sftp_auth_method' => 'password',
            'sftp_remote_path' => '/var/www/deploy.example',
        ]);

        Storage::disk('generated')->put("site{$site->id}/index.html", '<html>ok</html>');

        $mockSftp = \Mockery::mock(SftpClientInterface::class);
        $mockSftp->shouldReceive('connect')->once()->withArgs(function (Site $argSite) use ($site) {
            return $argSite->id === $site->id;
        })->andReturn(true);
        $mockSftp->shouldReceive('uploadDirectory')->once()->withArgs(function (Site $argSite, string $localPath, string $remotePath) use ($site) {
            return $argSite->id === $site->id
                && $localPath === "site{$site->id}"
                && $remotePath === '/var/www/deploy.example';
        })->andReturn(true);
        $mockSftp->shouldReceive('disconnect')->once();

        $this->app->instance(SftpClientInterface::class, $mockSftp);

        $deployResponse = $this->actingAs($this->admin)->postJson("/api/sites/{$site->id}/deploy");
        $deployResponse->assertOk();
        $deployResponse->assertJsonPath('status', 'completed');

        $this->assertDatabaseHas('deployments', [
            'site_id' => $site->id,
            'status' => 'completed',
        ]);
    }

    private function useTemporaryGeneratedDisk(): void
    {
        $root = '/tmp/laravel-static-generator-tests/generated-' . Str::uuid();
        File::ensureDirectoryExists($root);

        config()->set('filesystems.disks.generated.root', $root);
        Storage::forgetDisk('generated');

        $compiledViewsPath = '/tmp/laravel-static-generator-tests/views-' . Str::uuid();
        File::ensureDirectoryExists($compiledViewsPath);
        config()->set('view.compiled', $compiledViewsPath);
        app()->forgetInstance('blade.compiler');
    }
}
