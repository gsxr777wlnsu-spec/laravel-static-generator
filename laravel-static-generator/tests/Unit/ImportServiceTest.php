<?php

namespace Tests\Unit;

use App\Models\Site;
use App\Services\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ImportService::class);
        
        Storage::fake('generated');
    }

    public function test_import_creates_site_from_yaml(): void
    {
        Storage::disk('generated')->makeDirectory('site1/assets/images/logo');

        $data = [
            'domain' => 'test-import.com',
            'template' => 'base',
            'pages' => [
                [
                    'slug' => 'contact-us',
                    'title' => 'Contact Us',
                    'template_key' => 'contact-us',
                    'status' => 'published',
                    'sections' => [
                        [
                            'module' => 'hero',
                            'module_key' => 'hero',
                            'class' => 'hero',
                            'id' => 'hero',
                            'heading' => 'Contact Us',
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->service->importSite($data);

        $this->assertInstanceOf(Site::class, $result['site']);
        $this->assertEquals('test-import.com', $result['site']->domain);
        $this->assertEquals(1, $result['pages_count']);
        $this->assertCount(1, $result['pages']);
        $this->assertEquals('contact-us', $result['pages'][0]->slug);
        $this->assertEquals('Contact Us', $result['pages'][0]->title);
    }

    public function test_import_updates_existing_site(): void
    {
        Site::create([
            'name' => 'Existing Site',
            'domain' => 'existing.com',
            'template_set' => 'base',
            'status' => 'active',
            'output_path' => 'generated/existing',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);

        $data = [
            'domain' => 'existing.com',
            'template' => 'base',
            'pages' => [
                [
                    'slug' => 'new-page',
                    'title' => 'New Page',
                    'template_key' => 'blank',
                    'status' => 'published',
                ]
            ]
        ];

        $result = $this->service->importSite($data);

        $this->assertEquals('existing.com', $result['site']->domain);
        $this->assertEquals(1, $result['pages_count']);
    }

    public function test_import_requires_domain(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Missing 'domain' in import data");

        $this->service->importSite([
            'template' => 'base',
            'pages' => []
        ]);
    }

    public function test_list_import_templates(): void
    {
        $templates = $this->service->listImportTemplates();

        $this->assertIsArray($templates);
    }
}