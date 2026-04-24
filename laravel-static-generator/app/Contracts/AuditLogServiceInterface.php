<?php

namespace App\Contracts;

use App\Models\AuditLog;

interface AuditLogServiceInterface
{
    public function log(string $action, ?string $auditableType = null, ?int $auditableId = null, ?array $oldValues = null, ?array $newValues = null): AuditLog;
}
