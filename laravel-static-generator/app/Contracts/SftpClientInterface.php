<?php

namespace App\Contracts;

use App\Models\Site;

interface SftpClientInterface
{
    public function connect(Site $site): bool;
    public function testConnection(Site $site): bool;
    public function uploadDirectory(Site $site, string $localPath, string $remotePath): bool;
    public function runPostDeployCommands(Site $site, string $remotePath): void;
    public function disconnect(): void;
}
