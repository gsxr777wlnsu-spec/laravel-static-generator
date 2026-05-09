<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PageTemplateBootstrapTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-templates@test.com',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_page_templates_endpoint_returns_page_types(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/page-templates');

        $response->assertOk();
        $response->assertJsonStructure([
            'templates' => [
                '*' => ['key', 'label', 'source_file', 'default_slug'],
            ],
        ]);

        $keys = collect($response->json('templates'))->pluck('key')->all();

        $this->assertContains('blank', $keys);
        $this->assertContains('index', $keys);
        $this->assertContains('reviews', $keys);
    }

    public function test_creating_page_with_template_key_bootstraps_sections(): void
    {
        $site = $this->createSite();

        $response = $this->actingAs($this->admin)->postJson('/api/pages', [
            'site_id' => $site->id,
            'slug' => 'template-home',
            'title' => 'Template Home',
            'status' => 'draft',
            'locale' => 'en',
            'template_key' => 'index',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('page.template_key', 'index');
        $response->assertJson(fn ($json) => $json->where('sections_bootstrapped', fn ($count) => is_int($count) && $count > 0)->etc());

        $pageId = (int) $response->json('page.id');

        $this->assertDatabaseHas('pages', [
            'id' => $pageId,
            'template_key' => 'index',
        ]);

        $firstSection = Section::where('page_id', $pageId)->orderBy('order')->first();
        $this->assertNotNull($firstSection);
        $this->assertSame('module', $firstSection->type);
        $this->assertSame(0, (int) $firstSection->order);
        $this->assertSame('hero-main', $firstSection->content['module'] ?? null);
    }

    public function test_creating_page_auto_generates_canonical_from_site_domain_and_slug(): void
    {
        $site = $this->createSite();

        $response = $this->actingAs($this->admin)->postJson('/api/pages', [
            'site_id' => $site->id,
            'slug' => 'contact-us',
            'title' => 'Contact Us',
            'status' => 'draft',
            'locale' => 'en',
            'template_key' => 'blank',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('page.canonical', 'https://template-site.example/contact-us.html');
    }

    public function test_updating_slug_updates_auto_generated_canonical_in_response(): void
    {
        $site = $this->createSite();

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'old-slug',
            'title' => 'Old Slug',
            'status' => 'draft',
            'locale' => 'en',
            'template_key' => 'blank',
            'canonical' => 'https://template-site.example/old-slug',
        ]);

        $response = $this->actingAs($this->admin)->putJson("/api/pages/{$page->id}", [
            'slug' => 'new-slug',
            'canonical' => 'https://template-site.example/old-slug',
        ]);

        $response->assertOk();
        $response->assertJsonPath('canonical', 'https://template-site.example/new-slug.html');

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'slug' => 'new-slug',
            'canonical' => 'https://template-site.example/new-slug.html',
        ]);
    }

    public function test_bootstrap_endpoint_replaces_sections_and_updates_template_key(): void
    {
        $site = $this->createSite();

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'custom',
            'title' => 'Custom Page',
            'status' => 'draft',
            'locale' => 'en',
            'template_key' => 'blank',
        ]);

        Section::create([
            'page_id' => $page->id,
            'type' => 'text',
            'order' => 0,
            'content' => ['content' => '<p>Old content</p>'],
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/pages/{$page->id}/sections/bootstrap", [
            'template_key' => 'tips',
            'replace_existing' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('page.template_key', 'tips');
        $response->assertJson(fn ($json) => $json->where('sections_bootstrapped', fn ($count) => is_int($count) && $count > 0)->etc());

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'template_key' => 'tips',
        ]);

        $this->assertGreaterThan(1, Section::where('page_id', $page->id)->count());
    }

    private function createSite(): Site
    {
        return Site::create([
            'name' => 'Template Site',
            'domain' => 'template-site.example',
            'template_set' => 'base',
            'output_path' => 'generated/template-site',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);
    }
}
