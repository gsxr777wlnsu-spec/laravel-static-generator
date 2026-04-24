<?php

namespace App\Contracts;

use App\Models\Deployment;
use App\Models\Site;
use Illuminate\Database\Eloquent\Collection;

interface DeploymentRepositoryInterface
{
    public function create(array $data): Deployment;
    public function update(Deployment $deployment, array $data): Deployment;
    public function getBySite(Site $site): Collection;
    public function getByStatus(string $status): Collection;
}
