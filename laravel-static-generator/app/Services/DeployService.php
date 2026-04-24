<?php

namespace App\Services;

use App\Contracts\DeployServiceInterface;
use App\Contracts\DeploymentRepositoryInterface;
use App\Contracts\HtmlGeneratorInterface;
use App\Contracts\SftpClientInterface;
use App\Models\Deployment;
use App\Models\Site;
use Illuminate\Support\Facades\Storage;

class DeployService implements DeployServiceInterface
{
    public function __construct(
        private HtmlGeneratorInterface $generator,
        private DeploymentRepositoryInterface $deployments
    ) {}

    private function createSftpClient(): SftpClientInterface
    {
        return new SftpClient();
    }

    public function deploy(Site $site): Deployment
    {
        $deployment = $this->deployments->create([
            'site_id' => $site->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        try {
            $stagingPath = "site{$site->id}";
            
            if (!Storage::disk('generated')->exists($stagingPath)) {
                throw new \RuntimeException('Staging files not found. Please generate HTML first.');
            }

            $files = Storage::disk('generated')->allFiles($stagingPath);
            $filesCount = count($files);

            $sftp = $this->createSftpClient();
            $remotePath = $site->sftp_remote_path;
            if (empty($remotePath)) {
                $remotePath = '/var/www/' . $site->domain;
            }
            
            if (!$sftp->uploadDirectory($site, $stagingPath, $remotePath)) {
                throw new \RuntimeException('Failed to upload files to SFTP server');
            }

            $sftp->disconnect();

            $duration = max(0, now()->diffInSeconds($deployment->started_at));

            $this->deployments->update($deployment, [
                'status' => 'completed',
                'completed_at' => now(),
                'duration' => $duration,
                'files_count' => $filesCount,
                'sftp_host' => $site->sftp_host,
                'remote_path' => $remotePath,
                'log' => 'Deployment completed successfully',
            ]);

        } catch (\Exception $e) {
            $this->deployments->update($deployment, [
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
                'log' => 'Deployment failed: ' . $e->getMessage(),
            ]);

            \Log::error('Deployment failed', [
                'site_id' => $site->id,
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage()
            ]);
        }

        return $deployment->fresh();
    }

    public function checkConnection(Site $site): bool
    {
        return $this->sftp->testConnection($site);
    }

    public function rollback(Deployment $deployment): bool
    {
        try {
            $this->deployments->update($deployment, [
                'status' => 'rolled_back',
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Rollback failed', [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
