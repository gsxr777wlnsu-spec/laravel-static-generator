<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Log;

class GitService
{
    protected string $repositoryPath;

    public function __construct(string $repositoryPath = null)
    {
        $this->repositoryPath = $repositoryPath ?? base_path();
    }

    public function setRepositoryPath(string $path): self
    {
        $this->repositoryPath = $path;
        return $this;
    }

    public function init(): void
    {
        if (!is_dir($this->repositoryPath . '/.git')) {
            $this->runCommand(['git', 'init']);
            Log::info("Git initialized in {$this->repositoryPath}");
        }
    }

    public function commit(string $message): void
    {
        $this->init();
        
        $this->runCommand(['git', 'add', '.']);
        
        // Check if there's anything to commit
        $process = new Process(['git', 'status', '--porcelain']);
        $process->setWorkingDirectory($this->repositoryPath);
        $process->run();
        
        $status = $process->getOutput();
        if (empty(trim($status))) {
            return; // Nothing to commit
        }
        
        // Set user info if not set
        $this->runCommand(['git', 'config', 'user.name', 'AutoDeploy']);
        $this->runCommand(['git', 'config', 'user.email', 'deploy@localhost']);

        $this->runCommand(['git', 'commit', '-m', $message]);
        Log::info("Git commit: {$message}");
    }

    public function history(int $limit = 10): array
    {
        // Suppress failure if repo not initialized
        if (!is_dir($this->repositoryPath . '/.git')) {
            return [];
        }

        $process = new Process(['git', 'log', "-{$limit}", '--pretty=format:%H||%an||%ad||%s']);
        $process->setWorkingDirectory($this->repositoryPath);
        $process->run();

        $output = $process->getOutput();
        if (empty(trim($output))) {
            return [];
        }

        $commits = [];
        foreach (explode("\n", trim($output)) as $line) {
            $parts = explode('||', $line);
            if (count($parts) >= 4) {
                $commits[] = [
                    'hash' => $parts[0],
                    'author' => $parts[1],
                    'date' => $parts[2],
                    'message' => $parts[3]
                ];
            }
        }
        
        return $commits;
    }

    public function restore(string $hash): void
    {
        $this->runCommand(['git', 'checkout', $hash, '--', '.']);
        Log::info("Git restored to {$hash}");
    }

    protected function runCommand(array $command): string
    {
        $process = new Process($command);
        $process->setWorkingDirectory($this->repositoryPath);
        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }
}
