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
        private DeploymentRepositoryInterface $deployments,
        private SftpClientInterface $sftp
    ) {}

    public function deploy(Site $site, bool $runPostDeployCommands = false): Deployment
    {
        $deployment = $this->deployments->create([
            'site_id' => $site->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $backupPath = null;
        $backupReady = false;
        $connected = false;
        $remotePath = null;

        try {
            $stagingPath = "site{$site->id}";
            
            if (!Storage::disk('generated')->exists($stagingPath)) {
                throw new \RuntimeException('Staging files not found. Please generate HTML first.');
            }

            $files = Storage::disk('generated')->allFiles($stagingPath);
            $filesCount = count($files);

            $remotePath = $site->sftp_remote_path;
            if (empty($remotePath)) {
                $remotePath = '/var/www/' . $site->domain;
            }

            if (!$this->sftp->connect($site)) {
                throw new \RuntimeException('Failed to connect to SFTP server');
            }
            $connected = true;

            $backupPath = rtrim($remotePath, '/') . '.backup-' . now()->format('YmdHis') . '-' . $deployment->id;
            if (!$this->sftp->backupDirectory($site, $remotePath, $backupPath)) {
                throw new \RuntimeException('Failed to create remote backup before deployment');
            }
            $backupReady = true;

            if (!$this->sftp->uploadDirectory($site, $stagingPath, $remotePath)) {
                throw new \RuntimeException('Failed to upload files to SFTP server');
            }

            if (!$this->sftp->verifyUploadedFiles($site, $stagingPath, $remotePath)) {
                throw new \RuntimeException('Uploaded files verification failed');
            }

            if ($runPostDeployCommands) {
                $this->sftp->runPostDeployCommands($site, $remotePath);
            }

            $this->sftp->disconnect();

            $duration = max(0, now()->diffInSeconds($deployment->started_at));

            $this->deployments->update($deployment, [
                'status' => 'completed',
                'completed_at' => now(),
                'duration' => $duration,
                'files_count' => $filesCount,
                'sftp_host' => $site->sftp_host,
                'remote_path' => $remotePath,
                'backup_path' => $backupPath,
                'log' => 'Deployment completed successfully. Backup path: ' . $backupPath,
            ]);

        } catch (\Exception $e) {
            $restoreMessage = null;
            if ($connected && $backupReady && $backupPath !== null && $remotePath !== null) {
                try {
                    $restoreMessage = $this->sftp->restoreDirectory($site, $backupPath, $remotePath)
                        ? 'Rollback restored from backup: ' . $backupPath
                        : 'Rollback failed from backup: ' . $backupPath;
                } catch (\Throwable $rollbackException) {
                    $restoreMessage = 'Rollback failed: ' . $rollbackException->getMessage();
                }
            }

            try {
                $this->sftp->disconnect();
            } catch (\Throwable) {
                // Ignore disconnect issues in failure flow.
            }

            $this->deployments->update($deployment, [
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
                'backup_path' => $backupPath,
                'log' => trim('Deployment failed: ' . $e->getMessage() . "\n" . ($restoreMessage ?? '')),
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
            $deployment->loadMissing('site');
            $site = $deployment->site;

            if (!$site || !$deployment->backup_path || !$deployment->remote_path) {
                return false;
            }

            if (!$this->sftp->connect($site)) {
                return false;
            }

            if (!$this->sftp->restoreDirectory($site, $deployment->backup_path, $deployment->remote_path)) {
                $this->sftp->disconnect();
                return false;
            }

            $this->sftp->disconnect();

            $this->deployments->update($deployment, [
                'status' => 'rolled_back',
                'log' => trim((string) $deployment->log . "\nManual rollback restored from backup: " . $deployment->backup_path),
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
