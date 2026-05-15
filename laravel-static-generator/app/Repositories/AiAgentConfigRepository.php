<?php

namespace App\Repositories;

use App\Contracts\AiAgentConfigRepositoryInterface;
use App\Models\AiAgentConfig;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class AiAgentConfigRepository implements AiAgentConfigRepositoryInterface
{
    public function findForUser(int $userId): ?AiAgentConfig
    {
        if (!$this->tableExists()) {
            return null;
        }

        return AiAgentConfig::where('user_id', $userId)->first();
    }

    public function upsertForUser(int $userId, array $data): AiAgentConfig
    {
        if (!$this->tableExists()) {
            throw new RuntimeException("Table 'ai_agent_configs' is missing. Run database migrations.");
        }

        return AiAgentConfig::updateOrCreate(
            ['user_id' => $userId],
            $data
        );
    }

    private function tableExists(): bool
    {
        return Schema::hasTable('ai_agent_configs');
    }
}
