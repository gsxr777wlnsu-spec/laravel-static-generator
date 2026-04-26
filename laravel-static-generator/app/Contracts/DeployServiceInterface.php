<?php

namespace App\Contracts;

use App\Models\Deployment;
use App\Models\Site;

interface DeployServiceInterface
{
    public function deploy(Site $site, bool $runPostDeployCommands = false): Deployment;
    public function checkConnection(Site $site): bool;
    public function rollback(Deployment $deployment): bool;
}
