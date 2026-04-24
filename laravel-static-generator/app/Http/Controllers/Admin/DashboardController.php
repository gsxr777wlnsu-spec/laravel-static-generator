<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\SiteRepositoryInterface;
use App\Contracts\DeploymentRepositoryInterface;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function __construct(
        private SiteRepositoryInterface $sites,
        private DeploymentRepositoryInterface $deployments
    ) {}

    public function index()
    {
        $sites = $this->sites->getAll();
        $recentDeployments = $this->deployments->getByStatus('completed')
            ->sortByDesc('created_at')
            ->take(10);

        return view('admin.dashboard', compact('sites', 'recentDeployments'));
    }
}
