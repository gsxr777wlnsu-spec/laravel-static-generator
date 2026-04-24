<?php

namespace App\Contracts;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Collection;

interface AuditLogRepositoryInterface
{
    public function create(array $data): AuditLog;
    public function getByUser(int $userId): Collection;
    public function getByAction(string $action): Collection;
    public function getByDateRange(\DateTime $from, \DateTime $to): Collection;
}
