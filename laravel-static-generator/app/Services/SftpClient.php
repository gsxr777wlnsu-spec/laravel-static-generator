<?php

namespace App\Services;

use App\Contracts\SftpClientInterface;
use App\Models\Site;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;

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

    public function disconnect(): void
    {
        $this->filesystem = null;
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
