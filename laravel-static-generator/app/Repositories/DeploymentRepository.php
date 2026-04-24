<?php

namespace App\Repositories;

use App\Contracts\DeploymentRepositoryInterface;
use App\Models\Deployment;
use App\Models\Site;
use Illuminate\Database\Eloquent\Collection;

class DeploymentRepository implements DeploymentRepositoryInterface
{
    public function create(array $data): Deployment
    {
        return Deployment::create($data);
    }

    public function update(Deployment $deployment, array $data): Deployment
    {
        $deployment->update($data);
        return $deployment->fresh();
    }

    public function getBySite(Site $site): Collection
    {
        return Deployment::where('site_id', $site->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getByStatus(string $status): Collection
    {
        return Deployment::where('status', $status)->get();
    }
}
