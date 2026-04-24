<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminRoutesAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $compiledViewsPath = '/tmp/laravel-static-generator-tests/views-' . Str::uuid();
        File::ensureDirectoryExists($compiledViewsPath);
        config()->set('view.compiled', $compiledViewsPath);
        app()->forgetInstance('blade.compiler');
    }

    public function test_guest_is_redirected_to_login_for_admin_routes(): void
    {
        $site = $this->createSite();
        $page = $this->createPage($site);

        $routes = [
            '/admin',
            '/admin/sites',
            "/admin/sites/{$site->id}/pages",
            "/admin/sites/{$site->id}/pages/{$page->id}/edit",
            "/admin/sites/{$site->id}/media",
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertRedirect(route('login'));
        }
    }

    public function test_authenticated_user_can_open_main_admin_pages(): void
    {
        $admin = User::factory()->create();
        $site = $this->createSite();
        $page = $this->createPage($site);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard');

        $this->actingAs($admin)
            ->get('/admin/sites')
            ->assertOk()
            ->assertSee('Sites');

        $this->actingAs($admin)
            ->get("/admin/sites/{$site->id}/pages")
            ->assertOk()
            ->assertSee("Pages for {$site->name}");

        $this->actingAs($admin)
            ->get("/admin/sites/{$site->id}/pages/{$page->id}/edit")
            ->assertOk()
            ->assertSee('Apply Selected Template To Modules');
    }

    public function test_admin_layout_applies_dark_class_when_theme_cookie_is_dark(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->withCookie('color-theme', 'dark')
            ->get('/admin');

        $response->assertOk();
        $response->assertSee('<html lang="en" class="h-full dark">', false);
    }

    private function createSite(): Site
    {
        return Site::create([
            'name' => 'Admin Test Site',
            'domain' => 'admin-test.example',
            'template_set' => 'base',
            'output_path' => 'generated/admin-test',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);
    }

    private function createPage(Site $site): Page
    {
        return Page::create([
            'site_id' => $site->id,
            'slug' => 'index',
            'title' => 'Home',
            'status' => 'draft',
            'locale' => 'en',
            'template_key' => 'index',
        ]);
    }
}
