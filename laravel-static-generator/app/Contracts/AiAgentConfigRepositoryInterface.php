<?php

namespace App\Contracts;

use App\Models\AiAgentConfig;

interface AiAgentConfigRepositoryInterface
{
    public function findForUser(int $userId): ?AiAgentConfig;

    public function upsertForUser(int $userId, array $data): AiAgentConfig;
}
