<?php

namespace App\Console\Commands;

use App\Contracts\DeployServiceInterface;
use App\Contracts\HtmlGeneratorInterface;
use App\Contracts\SiteRepositoryInterface;
use Illuminate\Console\Command;

class SiteDeployCommand extends Command
{
    protected $signature = 'site:deploy {site_id : The ID of the site to deploy}';
    protected $description = 'Generate and deploy a site to production server';

    public function __construct(
        private HtmlGeneratorInterface $generator,
        private DeployServiceInterface $deploy,
        private SiteRepositoryInterface $sites
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $siteId = $this->argument('site_id');
        $site = $this->sites->findById($siteId);

        if (!$site) {
            $this->error("Site with ID {$siteId} not found");
            return self::FAILURE;
        }

        $this->info("Starting deployment for site: {$site->name} ({$site->domain})");

        $this->info('Step 1: Generating HTML...');
        $result = $this->generator->generateSite($site);

        if (!$result['success']) {
            $this->error('HTML generation failed');
            return self::FAILURE;
        }

        $this->info("Generated {$result['files_count']} files");

        $this->info('Step 2: Deploying to production...');
        $deployment = $this->deploy->deploy($site);

        if ($deployment->status === 'completed') {
            $this->info("Deployment completed successfully in {$deployment->duration}s");
            return self::SUCCESS;
        } else {
            $this->error("Deployment failed: {$deployment->error_message}");
            return self::FAILURE;
        }
    }
}
