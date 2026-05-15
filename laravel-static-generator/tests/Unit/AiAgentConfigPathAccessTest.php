<?php

namespace Tests\Unit;

use App\Models\AiAgentConfig;
use Tests\TestCase;

class AiAgentConfigPathAccessTest extends TestCase
{
    public function test_is_path_allowed_supports_wildcard_patterns(): void
    {
        $config = new AiAgentConfig([
            'allowed_paths' => ['/var/www/*'],
        ]);

        $this->assertTrue($config->isPathAllowed('/var/www/laravel-static-generator/storage/import-deploy/md/test/raw_html/site/1win-raw_html.md'));
    }

    public function test_is_path_allowed_denies_outside_path(): void
    {
        $config = new AiAgentConfig([
            'allowed_paths' => ['/var/www/*'],
        ]);

        $this->assertFalse($config->isPathAllowed('/etc/passwd'));
    }
}
