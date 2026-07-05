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

    public function test_closerouter_provider_can_be_saved(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/ai-agent/config', [
            'provider' => 'closerouter',
            'api_key' => 'closerouter-secret-key',
            'api_base_url' => 'https://api.closerouter.dev/v1',
            'model_name' => 'openai/gpt-4o-mini',
            'is_active' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('config.provider', 'closerouter');
        $response->assertJsonPath('config.api_base_url', 'https://api.closerouter.dev/v1');

        $this->assertDatabaseHas('ai_agent_configs', [
            'user_id' => $user->id,
            'provider' => 'closerouter',
            'api_base_url' => 'https://api.closerouter.dev/v1',
        ]);
    }

    public function test_authenticated_user_can_save_ai_model_slots(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/ai-agent/config', [
            'provider' => 'openrouter',
            'api_key' => 'openrouter-secret-key',
            'api_base_url' => 'https://openrouter.ai/api/v1',
            'model_name' => 'z-ai/glm-5.2',
            'ai_models' => [
                'big_main' => [
                    'provider' => 'openai',
                    'api_base_url' => 'https://api.openai.com/v1',
                    'model_name' => 'gpt-5.5',
                    'label' => 'Big main',
                ],
                'big_alternate' => [
                    'provider' => 'anthropic',
                    'api_base_url' => '',
                    'model_name' => 'claude-opus-4.9',
                    'label' => 'Big alternate',
                ],
                'medium_main' => [
                    'provider' => 'openrouter',
                    'api_key' => 'slot-secret-key',
                    'api_base_url' => 'https://openrouter.ai/api/v1',
                    'model_name' => 'z-ai/glm-5.2',
                    'label' => 'Medium main',
                    'temperature' => 0.2,
                    'tone' => 'strict',
                    'max_tokens' => 2048,
                    'top_p' => 0.9,
                    'frequency_penalty' => 0.1,
                    'presence_penalty' => 0.2,
                ],
                'medium_alternate' => [
                    'provider' => 'anthropic',
                    'api_base_url' => '',
                    'model_name' => 'claude-sonnet-5',
                    'label' => 'Medium alternate',
                ],
                'small_main' => [
                    'provider' => 'openrouter',
                    'api_base_url' => 'https://openrouter.ai/api/v1',
                    'model_name' => 'qwen/qwen3.3',
                    'label' => 'Small main',
                ],
                'small_alternate' => [
                    'provider' => 'anthropic',
                    'api_base_url' => '',
                    'model_name' => 'claude-haiku',
                    'label' => 'Small alternate',
                ],
            ],
            'is_active' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('config.ai_models.medium_main.model_name', 'z-ai/glm-5.2');
        $response->assertJsonPath('config.ai_models.medium_main.has_api_key', true);
        $response->assertJsonPath('config.ai_models.medium_main.temperature', 0.2);
        $response->assertJsonPath('config.ai_models.medium_main.tone', 'strict');
        $response->assertJsonPath('config.ai_models.medium_main.max_tokens', 2048);
        $response->assertJsonMissingPath('config.ai_models.medium_main.api_key');
        $response->assertJsonPath('config.ai_models.small_alternate.model_name', 'claude-haiku');

        $stored = DB::table('ai_agent_configs')
            ->where('user_id', $user->id)
            ->value('ai_models');

        $this->assertIsString($stored);
        $decoded = json_decode($stored, true);

        $this->assertSame('qwen/qwen3.3', $decoded['small_main']['model_name'] ?? null);
        $this->assertSame(2048, $decoded['medium_main']['max_tokens'] ?? null);
        $this->assertNotSame('slot-secret-key', $decoded['medium_main']['api_key'] ?? null);
        $this->assertStringNotContainsString('slot-secret-key', $stored);
    }
}
