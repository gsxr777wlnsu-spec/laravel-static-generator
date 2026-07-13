<?php

namespace App\Services;

use App\Models\AiPromptHistory;
use Illuminate\Support\Collection;

class AiPromptHistoryService
{
    /** @param array<string, string> $scope */
    public function list(array $scope): array
    {
        $query = AiPromptHistory::query()->where('scope_hash', $this->scopeHash($scope));

        return [
            'history' => (clone $query)->where('is_favorite', false)->latest('last_used_at')->latest('id')->limit(5)->get(),
            'favorites' => (clone $query)->where('is_favorite', true)->latest('updated_at')->get(),
        ];
    }

    /** @param array<string, string> $scope */
    public function record(array $scope, string $prompt): AiPromptHistory
    {
        $prompt = trim($prompt);
        $scope['scope_hash'] = $this->scopeHash($scope);
        $item = AiPromptHistory::updateOrCreate(
            $scope + ['prompt_hash' => hash('sha256', $prompt), 'is_favorite' => false],
            ['prompt' => $prompt, 'last_used_at' => now()]
        );

        $keep = AiPromptHistory::query()->where('scope_hash', $scope['scope_hash'])->where('is_favorite', false)
            ->latest('last_used_at')->latest('id')->limit(5)->pluck('id');
        AiPromptHistory::query()->where('scope_hash', $scope['scope_hash'])->where('is_favorite', false)->whereNotIn('id', $keep)->delete();

        return $item->fresh();
    }

    /** @param array<string, string> $scope */
    public function favorite(array $scope, string $prompt): AiPromptHistory
    {
        $prompt = trim($prompt);
        $scope['scope_hash'] = $this->scopeHash($scope);
        return AiPromptHistory::updateOrCreate(
            $scope + ['prompt_hash' => hash('sha256', $prompt), 'is_favorite' => true],
            ['prompt' => $prompt, 'last_used_at' => now()]
        );
    }

    /** @param array<string, string> $scope */
    private function scopeHash(array $scope): string
    {
        return hash('sha256', implode("\n", [
            $scope['template_set'], $scope['page_key'], $scope['module_key'], $scope['locale'], $scope['field_key'],
        ]));
    }
}
