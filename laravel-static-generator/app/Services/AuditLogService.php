<?php

namespace App\Services;

use App\Contracts\AuditLogRepositoryInterface;
use App\Contracts\AuditLogServiceInterface;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogService implements AuditLogServiceInterface
{
    public function __construct(
        private AuditLogRepositoryInterface $repository
    ) {}

    public function log(
        string $action,
        ?string $auditableType = null,
        ?int $auditableId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        return $this->repository->create([
            'user_id' => Auth::id() ?? 0,
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
