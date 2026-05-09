<?php

namespace App\Services;

use App\Contracts\SftpClientInterface;
use App\Models\Site;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;

class SftpClient implements SftpClientInterface
{
    public ?Filesystem $filesystem = null;

    public function connect(Site $site): bool
    {
        try {
            $credentials = $site->getSftpCredentials();
            
            if (!$credentials['host'] || !$credentials['username']) {
                throw new \InvalidArgumentException('SFTP host and username are required');
            }

            $config = [
                'host' => $credentials['host'],
                'port' => $credentials['port'] ?? 22,
                'username' => $credentials['username'],
                'timeout' => 30,
            ];

            $authMethod = strtolower($credentials['auth_method'] ?? '');

            if ($authMethod === 'password' && $credentials['password']) {
                $config['password'] = $this->decryptIfNeeded($credentials['password']);
            } elseif ($authMethod === 'key' && $credentials['private_key']) {
                $config['privateKey'] = $this->decryptIfNeeded($credentials['private_key']);
            }

            $provider = new SftpConnectionProvider(
                $config['host'],
                $config['username'],
                $config['password'] ?? null,
                $config['privateKey'] ?? null,
                null,
                $config['port'],
                false,
                $config['timeout']
            );

            $adapter = new SftpAdapter($provider, '/');
            $this->filesystem = new Filesystem($adapter);

            return true;
        } catch (\Exception $e) {
            \Log::error('SFTP connection failed', [
                'site_id' => $site->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function testConnection(Site $site): bool
    {
        try {
            if (!$this->connect($site)) {
                return false;
            }

            $this->filesystem->listContents('/')->toArray();
            $this->disconnect();
            
            return true;
        } catch (\Exception $e) {
            \Log::error('SFTP test connection failed', [
                'site_id' => $site->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function backupDirectory(Site $site, string $remotePath, string $backupPath): bool
    {
        try {
            if (!$this->filesystem && !$this->connect($site)) {
                return false;
            }

            $sourcePath = trim($remotePath, '/');
            $targetPath = trim($backupPath, '/');

            if (!$this->isSafeRemoteDirectoryPath($sourcePath) || !$this->isSafeRemoteDirectoryPath($targetPath)) {
                return false;
            }

            if ($this->filesystem->directoryExists($targetPath)) {
                $this->filesystem->deleteDirectory($targetPath);
            }

            if (!$this->filesystem->directoryExists($sourcePath)) {
                $this->filesystem->createDirectory($targetPath, ['visibility' => 'public']);
                return true;
            }

            return $this->copyRemoteDirectory($sourcePath, $targetPath);
        } catch (\Exception $e) {
            \Log::error('SFTP backup failed', [
                'site_id' => $site->id,
                'remote_path' => $remotePath,
                'backup_path' => $backupPath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function restoreDirectory(Site $site, string $backupPath, string $remotePath): bool
    {
        try {
            if (!$this->filesystem && !$this->connect($site)) {
                return false;
            }

            $sourcePath = trim($backupPath, '/');
            $targetPath = trim($remotePath, '/');

            if (!$this->isSafeRemoteDirectoryPath($sourcePath) || !$this->isSafeRemoteDirectoryPath($targetPath)) {
                return false;
            }

            if (!$this->filesystem->directoryExists($sourcePath)) {
                return false;
            }

            if ($this->filesystem->directoryExists($targetPath)) {
                $this->filesystem->deleteDirectory($targetPath);
            }

            return $this->copyRemoteDirectory($sourcePath, $targetPath);
        } catch (\Exception $e) {
            \Log::error('SFTP restore failed', [
                'site_id' => $site->id,
                'backup_path' => $backupPath,
                'remote_path' => $remotePath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function uploadDirectory(Site $site, string $localPath, string $remotePath): bool
    {
        try {
            if (!$this->filesystem) {
                if (!$this->connect($site)) {
                    return false;
                }
            }

            $normalizedRemotePath = trim($remotePath, '/');
            
            // Create root remote directory if it doesn't exist
            if ($normalizedRemotePath !== '') {
                try {
                    $this->filesystem->createDirectory($normalizedRemotePath, ['visibility' => 'public']);
                } catch (\Exception $e) {
                    // Might already exist
                }
            }

            $files = Storage::disk('generated')->allFiles($localPath);

            foreach ($files as $file) {
                $content = Storage::disk('generated')->get($file);
                $relativePath = str_replace($localPath . '/', '', $file);
                
                $remoteFile = $normalizedRemotePath !== ''
                    ? $normalizedRemotePath . '/' . ltrim($relativePath, '/')
                    : ltrim($relativePath, '/');

                $remoteDir = dirname($remoteFile);
                if ($remoteDir !== '.' && $remoteDir !== '' && $remoteDir !== $normalizedRemotePath) {
                    try {
                        $this->filesystem->createDirectory($remoteDir, ['visibility' => 'public']);
                    } catch (\Exception $e) {
                        // Directory might already exist
                    }
                }

                $this->filesystem->write($remoteFile, $content);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('SFTP upload failed', [
                'site_id' => $site->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function verifyUploadedFiles(Site $site, string $localPath, string $remotePath): bool
    {
        try {
            if (!$this->filesystem && !$this->connect($site)) {
                return false;
            }

            $normalizedRemotePath = trim($remotePath, '/');
            if (!$this->isSafeRemoteDirectoryPath($normalizedRemotePath)) {
                return false;
            }

            foreach (Storage::disk('generated')->allFiles($localPath) as $file) {
                $relativePath = str_replace($localPath . '/', '', $file);
                $remoteFile = $normalizedRemotePath . '/' . ltrim($relativePath, '/');

                if (!$this->filesystem->fileExists($remoteFile)) {
                    return false;
                }

                if ($this->filesystem->fileSize($remoteFile) !== Storage::disk('generated')->size($file)) {
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('SFTP upload verification failed', [
                'site_id' => $site->id,
                'remote_path' => $remotePath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function uploadFile(Site $site, string $localFilePath, string $remoteFilePath): bool
    {
        try {
            if (!$this->filesystem) {
                if (!$this->connect($site)) {
                    return false;
                }
            }

            $normalizedLocalFilePath = trim($localFilePath, '/');
            if ($normalizedLocalFilePath === '' || str_contains($normalizedLocalFilePath, '..')) {
                \Log::warning('Refused to upload unsafe local file path', [
                    'site_id' => $site->id,
                    'local_file_path' => $localFilePath,
                ]);
                return false;
            }

            if (!Storage::disk('generated')->exists($normalizedLocalFilePath)) {
                \Log::warning('Local file not found for upload', [
                    'site_id' => $site->id,
                    'local_file_path' => $normalizedLocalFilePath,
                ]);
                return false;
            }

            $normalizedRemoteFilePath = trim($remoteFilePath, '/');
            if ($normalizedRemoteFilePath === '' || str_contains($normalizedRemoteFilePath, '..')) {
                \Log::warning('Refused to upload unsafe remote file path', [
                    'site_id' => $site->id,
                    'remote_file_path' => $remoteFilePath,
                ]);
                return false;
            }

            $remoteDir = dirname($normalizedRemoteFilePath);
            if ($remoteDir !== '.' && $remoteDir !== '') {
                try {
                    $this->filesystem->createDirectory($remoteDir, ['visibility' => 'public']);
                } catch (\Exception) {
                    // Directory might already exist.
                }
            }

            $content = Storage::disk('generated')->get($normalizedLocalFilePath);
            $this->filesystem->write($normalizedRemoteFilePath, $content);
            return true;
        } catch (\Exception $e) {
            \Log::error('SFTP upload file failed', [
                'site_id' => $site->id,
                'local_file_path' => $localFilePath,
                'remote_file_path' => $remoteFilePath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function deleteDirectory(Site $site, string $remotePath): bool
    {
        try {
            if (!$this->filesystem) {
                if (!$this->connect($site)) {
                    return false;
                }
            }

            $normalizedRemotePath = trim($remotePath, '/');
            if (!$this->isSafeRemoteDirectoryPath($normalizedRemotePath)) {
                \Log::warning('Refused to delete unsafe remote directory path', [
                    'site_id' => $site->id,
                    'remote_path' => $remotePath,
                ]);
                return false;
            }

            if (!$this->filesystem->directoryExists($normalizedRemotePath)) {
                return true;
            }

            $this->filesystem->deleteDirectory($normalizedRemotePath);
            return true;
        } catch (\Exception $e) {
            \Log::error('SFTP delete directory failed', [
                'site_id' => $site->id,
                'remote_path' => $remotePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function deleteFile(Site $site, string $remoteFilePath): bool
    {
        try {
            if (!$this->filesystem) {
                if (!$this->connect($site)) {
                    return false;
                }
            }

            $normalizedRemoteFilePath = trim($remoteFilePath, '/');
            if ($normalizedRemoteFilePath === '') {
                \Log::warning('Refused to delete empty remote file path', [
                    'site_id' => $site->id,
                    'remote_file_path' => $remoteFilePath,
                ]);
                return false;
            }

            if (!$this->filesystem->fileExists($normalizedRemoteFilePath)) {
                return true;
            }

            $this->filesystem->delete($normalizedRemoteFilePath);
            return true;
        } catch (\Exception $e) {
            \Log::error('SFTP delete file failed', [
                'site_id' => $site->id,
                'remote_file_path' => $remoteFilePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function runPostDeployCommands(Site $site, string $remotePath): void
    {
        $normalizedRemotePath = rtrim(trim($remotePath), '/');
        if ($normalizedRemotePath === '') {
            throw new \RuntimeException('Remote path is required for post-deploy commands');
        }

        $targetPath = $normalizedRemotePath . '/';
        $ssh = $this->createSshConnection($site);

        foreach ([
            'chown -R www-data:www-data ' . escapeshellarg($targetPath),
            'chmod 755 ' . escapeshellarg($targetPath),
            'systemctl reload nginx',
        ] as $command) {
            $this->runSshCommandOrFail($ssh, $command);
        }
    }

    public function disconnect(): void
    {
        $this->filesystem = null;
    }

    private function createSshConnection(Site $site): SSH2
    {
        $credentials = $site->getSftpCredentials();
        $host = trim((string) ($credentials['host'] ?? ''));
        $username = trim((string) ($credentials['username'] ?? ''));
        $port = (int) ($credentials['port'] ?? 22);

        if ($host === '' || $username === '') {
            throw new \RuntimeException('SFTP host and username are required for post-deploy commands');
        }

        $ssh = new SSH2($host, $port, 30);
        $authMethod = strtolower((string) ($credentials['auth_method'] ?? ''));

        if ($authMethod === 'key' && !empty($credentials['private_key'])) {
            $privateKey = PublicKeyLoader::load($this->decryptIfNeeded((string) $credentials['private_key']));
            if (!$ssh->login($username, $privateKey)) {
                throw new \RuntimeException('SSH login failed using private key');
            }
            return $ssh;
        }

        $password = (string) ($credentials['password'] ?? '');
        if ($password === '') {
            throw new \RuntimeException('SSH password is required for post-deploy commands');
        }

        if (!$ssh->login($username, $this->decryptIfNeeded($password))) {
            throw new \RuntimeException('SSH login failed using password');
        }

        return $ssh;
    }

    private function runSshCommandOrFail(SSH2 $ssh, string $command): void
    {
        $wrappedCommand = 'sh -lc ' . escapeshellarg($command . ' 2>&1; printf "\n__EXIT_CODE:%s" "$?"');
        $output = $ssh->exec($wrappedCommand);

        if (!is_string($output) || !preg_match('/__EXIT_CODE:(\d+)\s*$/', $output, $matches)) {
            throw new \RuntimeException('Could not determine status for remote command: ' . $command);
        }

        $exitCode = (int) $matches[1];
        if ($exitCode !== 0) {
            $cleanOutput = trim((string) preg_replace('/__EXIT_CODE:\d+\s*$/', '', $output));
            $errorSuffix = $cleanOutput !== '' ? ' Output: ' . $cleanOutput : '';
            throw new \RuntimeException("Remote command failed ({$exitCode}): {$command}.{$errorSuffix}");
        }
    }

    private function copyRemoteDirectory(string $sourcePath, string $targetPath): bool
    {
        $this->filesystem->createDirectory($targetPath, ['visibility' => 'public']);

        foreach ($this->filesystem->listContents($sourcePath, true) as $item) {
            $sourceItemPath = $item->path();
            $relativePath = ltrim(substr($sourceItemPath, strlen($sourcePath)), '/');
            if ($relativePath === '') {
                continue;
            }

            $targetItemPath = $targetPath . '/' . $relativePath;

            if ($item->isDir()) {
                $this->filesystem->createDirectory($targetItemPath, ['visibility' => 'public']);
                continue;
            }

            $targetDir = dirname($targetItemPath);
            if ($targetDir !== '.' && !$this->filesystem->directoryExists($targetDir)) {
                $this->filesystem->createDirectory($targetDir, ['visibility' => 'public']);
            }

            $this->filesystem->write($targetItemPath, $this->filesystem->read($sourceItemPath));
        }

        return true;
    }

    private function decryptIfNeeded(string $value): string
    {
        try {
            return decrypt($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    private function isSafeRemoteDirectoryPath(string $remotePath): bool
    {
        $normalized = trim($remotePath, '/');
        if ($normalized === '' || str_contains($normalized, '..')) {
            return false;
        }

        $segments = array_values(array_filter(explode('/', $normalized), fn ($segment) => $segment !== ''));
        if (count($segments) < 2) {
            return false;
        }

        $protectedPaths = [
            'var',
            'var/www',
            'var/www/html',
            'etc',
            'usr',
            'home',
            'root',
            'tmp',
            'opt',
            'srv',
            'dev',
            'proc',
            'sys',
            'bin',
            'sbin',
            'lib',
            'lib64',
            'boot',
        ];

        return !in_array(strtolower($normalized), $protectedPaths, true);
    }
}
