<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SectionGeneratedBackgroundOverrideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $generatedRoot = '/tmp/laravel-static-generator-tests/generated-' . Str::uuid();
        File::ensureDirectoryExists($generatedRoot);

        config()->set('filesystems.disks.generated.root', $generatedRoot);
        Storage::forgetDisk('generated');
    }

    public function test_it_stores_generated_only_background_override_for_section(): void
    {
        $admin = User::factory()->create();
        $site = Site::create([
            'name' => 'Generated Override Site',
            'domain' => 'generated-override.example',
            'template_set' => 'base',
            'output_path' => 'generated/generated-override.example',
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

        $section = Section::create([
            'page_id' => $page->id,
            'type' => 'module',
            'order' => 0,
            'content' => [
                'module' => 'hero',
                'module_key' => 'hero',
                'raw_html' => '<section class="hero"><style>.hero { background-image: url(/assets/images/hero/hero-background.webp); }</style></section>',
                'render_mode' => 'raw_html',
            ],
        ]);

        $file = UploadedFile::fake()->create('override.webp', 12, 'image/webp');

        $response = $this->actingAs($admin)->post("/api/sections/{$section->id}/generated-background-override", [
            'file' => $file,
            'target_path' => 'assets/images/hero/hero-background.webp',
        ]);

        $response->assertOk()
            ->assertJsonPath('target_path', 'assets/images/hero/hero-background.webp')
            ->assertJsonPath('stored_path', "site{$site->id}/assets/images/hero/hero-background.webp")
            ->assertJsonPath('asset_url', '/assets/images/hero/hero-background.webp');

        Storage::disk('generated')->assertExists("site{$site->id}/assets/images/hero/hero-background.webp");
    }

    public function test_it_rejects_mime_extension_mismatch_for_generated_background_override(): void
    {
        $admin = User::factory()->create();
        $site = Site::create([
            'name' => 'Generated Override Site',
            'domain' => 'generated-override-2.example',
            'template_set' => 'base',
            'output_path' => 'generated/generated-override-2.example',
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

        $section = Section::create([
            'page_id' => $page->id,
            'type' => 'module',
            'order' => 0,
            'content' => [
                'module' => 'conclusion',
                'module_key' => 'conclusion',
                'raw_html' => '<section class="conclusion"></section>',
                'render_mode' => 'raw_html',
            ],
        ]);

        $file = UploadedFile::fake()->create('override.png', 12, 'image/png');

        $this->actingAs($admin)->post("/api/sections/{$section->id}/generated-background-override", [
            'file' => $file,
            'target_path' => 'assets/images/hero/conclusion-background.webp',
        ])->assertStatus(422)
            ->assertJsonPath('error', 'Uploaded file type does not match target extension');

        Storage::disk('generated')->assertMissing("site{$site->id}/assets/images/hero/conclusion-background.webp");
    }
}
