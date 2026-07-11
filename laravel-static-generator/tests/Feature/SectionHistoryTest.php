<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Section;
use App\Models\SectionHistory;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SectionHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_section_update_creates_history_and_restore_reverts_module_content(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'section-history@test.com',
            'password' => Hash::make('password'),
        ]);

        $site = Site::create([
            'name' => 'History Site',
            'domain' => 'history.example',
            'template_set' => 'base',
            'output_path' => 'generated/history',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'index',
            'title' => 'History Page',
            'status' => 'draft',
            'locale' => 'en',
        ]);

        $section = Section::create([
            'page_id' => $page->id,
            'type' => 'module',
            'order' => 0,
            'content' => [
                'module' => 'hero',
                'raw_html' => '<section>Old</section>',
            ],
        ]);

        $updateResponse = $this->actingAs($user)->putJson("/api/sections/{$section->id}", [
            'type' => 'module',
            'content' => [
                'module' => 'hero',
                'raw_html' => '<section>New</section>',
            ],
        ]);

        $updateResponse->assertOk();
        $history = SectionHistory::where('section_id', $section->id)->first();
        $this->assertNotNull($history);
        $this->assertSame('<section>Old</section>', $history->content['raw_html']);

        for ($i = 0; $i < 11; $i++) {
            $updateResponse = $this->actingAs($user)->putJson("/api/sections/{$section->id}", [
                'type' => 'module',
                'content' => [
                    'module' => 'hero',
                    'raw_html' => "<section>{$i}</section>",
                ],
            ]);
            $updateResponse->assertOk();
        }

        $this->assertSame(10, SectionHistory::where('section_id', $section->id)->count());
        $history = SectionHistory::where('section_id', $section->id)->orderByDesc('id')->first();
        $this->assertNotNull($history);
        $expectedRestoredHtml = $history->content['raw_html'];

        $listResponse = $this->actingAs($user)->getJson("/api/sections/{$section->id}/history");
        $listResponse->assertOk();
        $this->assertCount(10, $listResponse->json('histories'));

        $restoreResponse = $this->actingAs($user)->postJson("/api/sections/{$section->id}/history/{$history->id}/restore");
        $restoreResponse->assertOk();

        $section->refresh();
        $this->assertSame($expectedRestoredHtml, $section->content['raw_html']);
    }
}
