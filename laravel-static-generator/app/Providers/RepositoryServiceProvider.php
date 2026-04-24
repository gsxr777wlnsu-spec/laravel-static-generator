<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\SiteRepositoryInterface;
use App\Contracts\PageRepositoryInterface;
use App\Contracts\SectionRepositoryInterface;
use App\Contracts\MediaRepositoryInterface;
use App\Contracts\DeploymentRepositoryInterface;
use App\Contracts\AuditLogRepositoryInterface;
use App\Repositories\SiteRepository;
use App\Repositories\PageRepository;
use App\Repositories\SectionRepository;
use App\Repositories\MediaRepository;
use App\Repositories\DeploymentRepository;
use App\Repositories\AuditLogRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SiteRepositoryInterface::class, SiteRepository::class);
        $this->app->bind(PageRepositoryInterface::class, PageRepository::class);
        $this->app->bind(SectionRepositoryInterface::class, SectionRepository::class);
        $this->app->bind(MediaRepositoryInterface::class, MediaRepository::class);
        $this->app->bind(DeploymentRepositoryInterface::class, DeploymentRepository::class);
        $this->app->bind(AuditLogRepositoryInterface::class, AuditLogRepository::class);
        
        $this->app->bind(\App\Contracts\SeoServiceInterface::class, \App\Services\SeoService::class);
        $this->app->bind(\App\Contracts\SectionServiceInterface::class, \App\Services\SectionService::class);
        $this->app->bind(\App\Contracts\MediaManagerInterface::class, \App\Services\MediaManagerService::class);
        $this->app->bind(\App\Contracts\ImageProcessorInterface::class, \App\Services\ImageProcessorService::class);
        $this->app->bind(\App\Contracts\HtmlGeneratorInterface::class, \App\Services\HtmlGeneratorService::class);
        $this->app->bind(\App\Contracts\SftpClientInterface::class, \App\Services\SftpClient::class);
        $this->app->bind(\App\Contracts\DeployServiceInterface::class, \App\Services\DeployService::class);
        $this->app->bind(\App\Contracts\AuditLogServiceInterface::class, \App\Services\AuditLogService::class);
    }

    public function boot(): void
    {
        //
    }
}
