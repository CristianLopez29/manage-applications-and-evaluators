<?php

namespace Src\Shared\Domain\Audit;

interface AuditLogger
{
    public function log(string $action, string $entityType, string $entityId, array $payload = []): void;
}
