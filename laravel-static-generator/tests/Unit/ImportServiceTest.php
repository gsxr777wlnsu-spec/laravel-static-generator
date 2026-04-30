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

        $root = '/tmp/laravel-static-generator-tests/generated-' . Str::uuid();
        File::ensureDirectoryExists($root);
        config()->set('filesystems.disks.generated.root', $root);
        Storage::forgetDisk('generated');
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
