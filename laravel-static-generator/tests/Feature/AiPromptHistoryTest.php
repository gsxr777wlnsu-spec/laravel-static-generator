<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiPromptHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_keeps_five_recent_prompts_and_unlimited_favorites(): void
    {
        $user = User::factory()->create();
        $scope = ['template_set' => 'base', 'page_key' => 'demo', 'module_key' => 'casino', 'locale' => 'en', 'field_key' => 'module_prompt'];

        foreach (range(1, 6) as $number) {
            $this->actingAs($user)->postJson('/api/ai-prompt-history/record', $scope + ['prompt' => "Prompt {$number}"])->assertCreated();
        }
        $this->actingAs($user)->postJson('/api/ai-prompt-history/favorite', $scope + ['prompt' => 'Favorite'])->assertCreated();

        $response = $this->actingAs($user)->getJson('/api/ai-prompt-history?' . http_build_query($scope))->assertOk();
        $this->assertCount(5, $response->json('history'));
        $this->assertSame('Prompt 6', $response->json('history.0.prompt'));
        $this->assertCount(1, $response->json('favorites'));
        $this->assertDatabaseMissing('ai_prompt_histories', ['prompt' => 'Prompt 1', 'is_favorite' => false]);
        $this->assertDatabaseHas('ai_prompt_histories', ['prompt' => 'Favorite', 'is_favorite' => true]);
    }
}
