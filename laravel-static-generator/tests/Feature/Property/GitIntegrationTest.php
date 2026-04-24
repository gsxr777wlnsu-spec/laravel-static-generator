<?php

namespace Tests\Feature\Property;

use Tests\TestCase;
use App\Services\GitService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class GitIntegrationTest extends TestCase
{
    protected string $testRepoPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testRepoPath = '/tmp/laravel-static-generator-tests/git_test_' . uniqid('', true);
        if (!is_dir($this->testRepoPath)) {
            mkdir($this->testRepoPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->testRepoPath);
        parent::tearDown();
    }

    /**
     * Property 23: Git commit on template change.
     */
    public function test_git_commit_records_history()
    {
        $gitService = new GitService($this->testRepoPath);
        
        $gitService->init();
        
        file_put_contents($this->testRepoPath . '/test.txt', 'Hello World');
        $gitService->commit('Initial commit');
        
        $history = $gitService->history();
        $this->assertCount(1, $history);
        $this->assertEquals('Initial commit', $history[0]['message']);
        
        file_put_contents($this->testRepoPath . '/test.txt', 'Changed text');
        $gitService->commit('Update text');
        
        $history = $gitService->history();
        $this->assertCount(2, $history); // Newest first
        $this->assertEquals('Update text', $history[0]['message']);
    }

    /**
     * Property 24: Gitignore excludes secrets.
     */
    public function test_gitignore_excludes_secrets()
    {
        $gitService = new GitService($this->testRepoPath);
        
        $gitService->init();
        
        file_put_contents($this->testRepoPath . '/.gitignore', ".env\n*.key");
        file_put_contents($this->testRepoPath . '/.env', "SECRET=123");
        file_put_contents($this->testRepoPath . '/secret.key', "---KEY---");
        file_put_contents($this->testRepoPath . '/public.txt', "Public data");
        
        $gitService->commit('Add files');
        
        // Execute git ls-files manually to verify what was tracked
        $process = new \Symfony\Component\Process\Process(['git', 'ls-files']);
        $process->setWorkingDirectory($this->testRepoPath);
        $process->run();
        
        $trackedFiles = $process->getOutput();
        
        $this->assertStringContainsString('public.txt', $trackedFiles);
        $this->assertStringContainsString('.gitignore', $trackedFiles);
        
        $this->assertStringNotContainsString('.env', $trackedFiles);
        $this->assertStringNotContainsString('secret.key', $trackedFiles);
    }
}
