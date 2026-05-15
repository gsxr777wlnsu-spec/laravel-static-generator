<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AiAgentConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_save_ai_agent_config_with_encrypted_api_key(): void
    {
        $user = User::factory()->create();

        $payload = [
            'provider' => 'openai',
            'api_key' => 'plain-secret-key',
            'api_base_url' => 'https://api.openai.com/v1',
            'model_name' => 'gpt-4o-mini',
            'temperature' => 0.4,
            'tone' => 'concise',
            'max_tokens' => 1200,
            'allowed_paths' => ['/var/www/laravel-static-generator/storage/import-deploy/md/test/raw_html'],
            'allowed_sites' => [1, 2],
            'is_active' => true,
        ];

        $response = $this->actingAs($user)->putJson('/api/ai-agent/config', $payload);
        $response->assertOk();
        $response->assertJsonPath('config.provider', 'openai');
        $response->assertJsonPath('config.has_api_key', true);
        $response->assertJsonMissingPath('config.api_key');

        $this->assertDatabaseHas('ai_agent_configs', [
            'user_id' => $user->id,
            'provider' => 'openai',
            'model_name' => 'gpt-4o-mini',
            'is_active' => 1,
        ]);

        $stored = (string) DB::table('ai_agent_configs')
            ->where('user_id', $user->id)
            ->value('api_key');

        $this->assertNotSame('plain-secret-key', $stored);
        $this->assertStringNotContainsString('plain-secret-key', $stored);
    }

    public function test_provider_is_validated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/ai-agent/config', [
            'provider' => 'unknown-provider',
            'api_key' => 'x',
            'is_active' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['provider']);
    }
}
