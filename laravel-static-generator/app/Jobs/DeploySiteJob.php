<?php

namespace App\Jobs;

use App\Contracts\DeployServiceInterface;
use App\Contracts\HtmlGeneratorInterface;
use App\Contracts\SftpClientInterface;
use App\Models\Site;
use App\Models\User;
use App\Notifications\DeploymentStatusNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeploySiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    public function __construct(
        public Site $site,
        public ?int $userId = null
    ) {}

    public function handle(
        DeployServiceInterface $deploy,
        HtmlGeneratorInterface $generator,
        SftpClientInterface $sftp
    ): void
    {
        try {
            $generation = $generator->generateSite($this->site);
            if (($generation['success'] ?? false) !== true) {
                throw new \RuntimeException('HTML generation failed, deployment skipped.');
            }

            if (!$sftp->testConnection($this->site)) {
                throw new \RuntimeException('Could not connect to SFTP server. Please check SFTP settings.');
            }

            $deployment = $deploy->deploy($this->site, true);

            if ($this->userId) {
                $user = User::find($this->userId);
                if ($user) {
                    // Deployment model has string status
                    $success = $deployment->status === 'completed' || $deployment->status === 'success';
                    $user->notify(new DeploymentStatusNotification($this->site, $success));
                }
            }
        } catch (\Exception $e) {
            if ($this->userId) {
                $user = User::find($this->userId);
                if ($user) {
                    $user->notify(new DeploymentStatusNotification($this->site, false, $e->getMessage()));
                }
            }
            throw $e;
        }
    }
}
