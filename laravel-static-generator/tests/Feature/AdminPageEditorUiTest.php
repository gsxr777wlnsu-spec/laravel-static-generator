<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminPageEditorUiTest extends TestCase
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

    public function test_edit_page_contains_template_selector_and_module_catalog(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-page-ui@test.com',
            'password' => Hash::make('password'),
        ]);

        $site = Site::create([
            'name' => 'UI Site',
            'domain' => 'ui-site.example',
            'template_set' => 'base',
            'output_path' => 'generated/ui-site',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'index',
            'title' => 'Home',
            'status' => 'draft',
            'locale' => 'en',
            'template_key' => 'index',
        ]);

        $response = $this->actingAs($admin)->get("/admin/sites/{$site->id}/pages/{$page->id}/edit");

        $response->assertOk();
        $response->assertSee('name="template_key"', false);
        $response->assertSee('Apply Selected Template To Modules');
        $response->assertSee('Modules');
        $response->assertDontSee('Add Section');
        $response->assertDontSee('Section Type');
    }
}
