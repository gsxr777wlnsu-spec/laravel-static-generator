<?php

namespace App\Repositories;

use App\Contracts\AuditLogRepositoryInterface;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Collection;

class AuditLogRepository implements AuditLogRepositoryInterface
{
    public function create(array $data): AuditLog
    {
        return AuditLog::create($data);
    }

    public function getByUser(int $userId): Collection
    {
        return AuditLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getByAction(string $action): Collection
    {
        return AuditLog::where('action', $action)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getByDateRange(\DateTime $from, \DateTime $to): Collection
    {
        return AuditLog::whereBetween('created_at', [$from, $to])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
