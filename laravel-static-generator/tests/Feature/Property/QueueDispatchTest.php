<?php

namespace Tests\Feature\Property;

use Tests\TestCase;
use App\Models\Site;
use App\Models\Page;
use App\Models\User;
use App\Jobs\GenerateSiteJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class QueueDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
        ]);
    }

    /**
     * Property 29: Queue job creation threshold.
     */
    public function test_generate_endpoint_dispatches_job_over_threshold()
    {
        Queue::fake();
        
        $site = Site::create([
            'name' => 'Test',
            'domain' => 'test1.com',
            'template_set' => 'default',
            'output_path' => '/var/www'
        ]);
        
        $pages = [];
        for ($i=0; $i < 101; $i++) {
            $pages[] = [
                'site_id' => $site->id,
                'slug' => "page-$i",
                'title' => "Page $i",
                'status' => 'published',
                'locale' => 'en',
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        Page::insert($pages);
        
        $response = $this->actingAs($this->admin)->postJson("/api/sites/{$site->id}/generate");
        
        $response->assertStatus(202);
        $response->assertJsonPath('message', 'Site generation dispatched to queue');
        
        Queue::assertPushed(GenerateSiteJob::class, function ($job) use ($site) {
            return $job->site->id === $site->id;
        });
    }

    public function test_generate_endpoint_runs_sync_under_threshold()
    {
        Queue::fake();
        
        $site = Site::create([
            'name' => 'Test',
            'domain' => 'test2.com',
            'template_set' => 'default',
            'output_path' => '/var/www'
        ]);
        
        // Mock HtmlGeneratorInterface so it doesn't actually hit the disk/services
        $mockGenerator = \Mockery::mock(\App\Contracts\HtmlGeneratorInterface::class);
        $mockGenerator->shouldReceive('generateSite')->once()->andReturn([
            'success' => true,
            'files_count' => 10,
            'generated_files' => [],
            'errors' => []
        ]);
        $this->app->instance(\App\Contracts\HtmlGeneratorInterface::class, $mockGenerator);
        
        // Mock PageRepositoryInterface because controller looks for it
        $mockPageRepo = \Mockery::mock(\App\Contracts\PageRepositoryInterface::class);
        $mockPageRepo->shouldReceive('getActiveBySite')->once()->andReturn(
            \Illuminate\Database\Eloquent\Collection::make(array_fill(0, 10, new Page()))
        );
        $this->app->instance(\App\Contracts\PageRepositoryInterface::class, $mockPageRepo);
        
        // Mock AuditLogServiceInterface
        $mockAudit = \Mockery::mock(\App\Contracts\AuditLogServiceInterface::class);
        $mockAudit->shouldReceive('log')->once();
        $this->app->instance(\App\Contracts\AuditLogServiceInterface::class, $mockAudit);
        
        // Remove route caching from cache driver or anything if needed
        $response = $this->actingAs($this->admin)->postJson("/api/sites/{$site->id}/generate");
        
        $response->assertStatus(200);
        Queue::assertNotPushed(GenerateSiteJob::class);
    }

    /**
     * Property 30: Queue progress tracking.
     */
    public function test_generate_site_job_updates_progress()
    {
        $site = Site::create([
            'name' => 'Test',
            'domain' => 'test3.com',
            'template_set' => 'default',
            'output_path' => '/var/www'
        ]);
        
        $job = new GenerateSiteJob($site, 1);
        
        $mockGenerator = \Mockery::mock(\App\Contracts\HtmlGeneratorInterface::class);
        $mockGenerator->shouldReceive('generateSite')->once()->andReturnUsing(function($site, $onProgress) {
            $onProgress(50, 100);
            return ['success' => true, 'files_count' => 50];
        });
        
        $job->handle($mockGenerator);
        
        $cache = Cache::get("site_generation_progress_{$site->id}");
        
        // Because the mock completed, the last put on cache is 100%.
        $this->assertEquals(100, $cache['progress']);
        $this->assertEquals('completed', $cache['status']);
        $this->assertEquals(50, $cache['result']['files_count']);
    }
}
