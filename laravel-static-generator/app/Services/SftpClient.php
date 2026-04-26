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

    private function decryptIfNeeded(string $value): string
    {
        try {
            return decrypt($value);
        } catch (\Throwable) {
            return $value;
        }
    }
}
