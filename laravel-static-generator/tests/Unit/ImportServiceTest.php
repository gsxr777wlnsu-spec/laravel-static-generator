<?php

namespace Tests\Unit;

use App\Models\Site;
use App\Services\ImportService;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ImportService::class);

        $generatedRoot = '/tmp/laravel-static-generator-tests/generated-' . Str::uuid();
        File::ensureDirectoryExists($generatedRoot);
        config()->set('filesystems.disks.generated.root', $generatedRoot);
        Storage::forgetDisk('generated');

        $sitesRoot = '/tmp/laravel-static-generator-tests/sites-' . Str::uuid();
        File::ensureDirectoryExists($sitesRoot);
        config()->set('filesystems.disks.sites.root', $sitesRoot);
        Storage::forgetDisk('sites');
    }

    public function test_import_creates_site_from_yaml(): void
    {
        Storage::disk('generated')->put('site1/assets/css/style.css', 'body{color:#111;}');
        Storage::disk('generated')->put('site1/assets/js/app.js', 'console.log("etalon");');
        Storage::disk('generated')->put('site1/assets/images/logo/logo.svg', '<svg></svg>');

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
        Storage::disk('sites')->assertExists($result['site']->id . '/assets/css/style.css');
        Storage::disk('sites')->assertExists($result['site']->id . '/assets/js/app.js');
        Storage::disk('sites')->assertExists($result['site']->id . '/assets/js/main.js');
        Storage::disk('sites')->assertExists($result['site']->id . '/assets/images/logo/logo.svg');
    }

    public function test_import_uses_latest_preview_assets_when_etalon_is_incomplete(): void
    {
        Storage::disk('generated')->makeDirectory('site1/assets/images/logo');
        Storage::disk('generated')->put('preview/token-a/assets/css/style.css', 'body{background:#fff;}');
        Storage::disk('generated')->put('preview/token-a/assets/js/app.js', 'console.log("preview");');
        Storage::disk('generated')->put('preview/token-a/assets/images/logo/logo.svg', '<svg>preview</svg>');

        $result = $this->service->importSite([
            'domain' => 'preview-import.com',
            'template' => 'base',
            'pages' => [],
        ]);

        Storage::disk('sites')->assertExists($result['site']->id . '/assets/css/style.css');
        Storage::disk('sites')->assertExists($result['site']->id . '/assets/js/app.js');
        Storage::disk('sites')->assertExists($result['site']->id . '/assets/js/main.js');
        Storage::disk('sites')->assertExists($result['site']->id . '/assets/images/logo/logo.svg');
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

    public function test_import_replaces_raw_html_placeholders_from_section_fields(): void
    {
        $result = $this->service->importSite([
            'domain' => 'raw-html-placeholders.com',
            'template' => 'test',
            'pages' => [
                [
                    'slug' => 'authors',
                    'title' => 'Authors',
                    'template_key' => 'authors',
                    'status' => 'published',
                    'sections' => [
                        [
                            'module' => 'hero',
                            'module_key' => 'hero',
                            'author_name' => 'Rahul Kumar Gupta',
                            'hero_image' => '/assets/images/kishor-singha-hero.webp',
                            'hero_alt' => 'Rahul Kumar Gupta',
                            'raw_html' => '<section><img src="[[hero_image]]" alt="[[hero_alt]]"><h1>[[author_name]]</h1><p>[[unknown]]</p></section>',
                        ],
                    ],
                ],
            ],
        ]);

        $section = $result['pages'][0]->sections()->first();

        $this->assertStringContainsString('src="/assets/images/kishor-singha-hero.webp"', $section->raw_html);
        $this->assertStringContainsString('alt="Rahul Kumar Gupta"', $section->raw_html);
        $this->assertStringContainsString('<h1>Rahul Kumar Gupta</h1>', $section->raw_html);
        $this->assertStringContainsString('<p>[[unknown]]</p>', $section->raw_html);
        $this->assertSame($section->raw_html, $section->content['raw_html']);
    }

    public function test_import_ignores_universal_content_blocks(): void
    {
        $data = [
            'domain' => 'universal-import.com',
            'template' => 'test',
            'pages' => [
                [
                    'slug' => 'index',
                    'title' => 'Index',
                    'template_key' => 'index',
                    'status' => 'published',
                    'sections' => [
                        [
                            'module' => 'characteristics',
                            'module_key' => 'characteristics',
                            'contentBlocks' => [
                                [
                                    'type' => 'h6',
                                    'text' => 'TEST Heading 6',
                                ],
                                [
                                    'type' => 'ordered_list',
                                    'items' => ['Step one', 'Step two'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->service->importSite($data);
        $section = $result['pages'][0]->sections()->first();

        $this->assertStringContainsString('<section class="characteristics"', $section->raw_html);
        $this->assertStringNotContainsString('<h6>TEST Heading 6</h6>', $section->raw_html);
        $this->assertStringNotContainsString('Step one', $section->raw_html);
    }

    public function test_import_replaces_default_module_text_from_section_fields(): void
    {
        $data = [
            'domain' => 'module-text-import.com',
            'template' => 'test',
            'pages' => [
                [
                    'slug' => 'index',
                    'title' => 'Index',
                    'template_key' => 'index',
                    'status' => 'published',
                    'sections' => [
                        [
                            'module' => 'characteristics',
                            'module_key' => 'characteristics',
                            'heading' => 'Changed Characteristics Heading',
                            'description' => 'Changed characteristics description.',
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->service->importSite($data);
        $section = $result['pages'][0]->sections()->first();

        $this->assertStringContainsString('<section class="characteristics"', $section->raw_html);
        $this->assertStringContainsString('Changed Characteristics Heading', $section->raw_html);
        $this->assertStringContainsString('Changed characteristics description.', $section->raw_html);
    }

    public function test_import_replaces_default_module_text_from_content_blocks(): void
    {
        $data = [
            'domain' => 'module-content-blocks-import.com',
            'template' => 'test',
            'pages' => [
                [
                    'slug' => 'index',
                    'title' => 'Index',
                    'template_key' => 'index',
                    'status' => 'published',
                    'sections' => [
                        [
                            'module' => 'characteristics',
                            'module_key' => 'characteristics',
                            'contentBlocks' => [
                                [
                                    'type' => 'paragraph',
                                    'text' => 'Changed paragraph from contentBlocks.',
                                ],
                                [
                                    'type' => 'table',
                                    'rows' => [
                                        ['Changed Label', 'Changed Value'],
                                    ],
                                ],
                                [
                                    'type' => 'paragraph',
                                    'text' => 'Changed limited paragraph from contentBlocks.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->service->importSite($data);
        $section = $result['pages'][0]->sections()->first();

        $this->assertStringContainsString('class="characteristics__description">Changed paragraph from contentBlocks.</p>', $section->raw_html);
        $this->assertStringContainsString('<span class="characteristics__label">Changed Label</span>', $section->raw_html);
        $this->assertStringContainsString('class="characteristics__cell characteristics__value">Changed Value</td>', $section->raw_html);
        $this->assertStringContainsString('class="text text--limited">Changed limited paragraph from contentBlocks.</p>', $section->raw_html);
    }

    public function test_import_replaces_payment_table_and_bottom_paragraph_from_content_blocks(): void
    {
        $data = [
            'domain' => 'rtp-content-blocks-import.com',
            'template' => 'test',
            'pages' => [
                [
                    'slug' => 'index',
                    'title' => 'Index',
                    'template_key' => 'index',
                    'status' => 'published',
                    'sections' => [
                        [
                            'module' => 'rtp',
                            'module_key' => 'rtp',
                            'contentBlocks' => [
                                [
                                    'type' => 'h2',
                                    'text' => 'Changed RTP Heading',
                                ],
                                [
                                    'type' => 'paragraph',
                                    'text' => 'Changed RTP top paragraph.',
                                ],
                                [
                                    'type' => 'table',
                                    'rows' => [
                                        ['Changed Method', 'Changed Availability', 'Changed Minimum', 'Changed Time', 'Changed Fees'],
                                    ],
                                ],
                                [
                                    'type' => 'paragraph',
                                    'text' => 'Changed RTP bottom paragraph.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->service->importSite($data);
        $section = $result['pages'][0]->sections()->first();

        $this->assertStringContainsString('<h2 class="symbols__title">Changed RTP Heading</h2>', $section->raw_html);
        $this->assertStringContainsString('class="symbols__description">Changed RTP top paragraph.</p>', $section->raw_html);
        $this->assertStringContainsString('<td class="payments__cell">Changed Method</td>', $section->raw_html);
        $this->assertStringContainsString('<td class="payments__cell">Changed Availability</td>', $section->raw_html);
        $this->assertStringContainsString('<td class="payments__cell">Changed Minimum</td>', $section->raw_html);
        $this->assertStringContainsString('<td class="payments__cell">Changed Time</td>', $section->raw_html);
        $this->assertStringContainsString('<td class="payments__cell">Changed Fees</td>', $section->raw_html);
        $this->assertStringContainsString('class="text text--pt20">Changed RTP bottom paragraph.</p>', $section->raw_html);
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
