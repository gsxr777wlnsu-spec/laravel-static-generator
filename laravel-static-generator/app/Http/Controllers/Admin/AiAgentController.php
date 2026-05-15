<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\AiAgentConfigRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\AiAgentService;
use Illuminate\Contracts\View\View;

class AiAgentController extends Controller
{
    public function __construct(
        private AiAgentConfigRepositoryInterface $configs,
        private AiAgentService $aiAgentService
    ) {}

    public function edit(): View
    {
        $user = auth()->user();
        $config = $user ? $this->configs->findForUser((int) $user->id) : null;

        $sites = Site::query()
            ->orderBy('name')
            ->get(['id', 'name', 'domain']);

        return view('admin.ai-agent.edit', [
            'config' => $config,
            'sites' => $sites,
            'providers' => $this->aiAgentService->providerOptions(),
        ]);
    }
}
