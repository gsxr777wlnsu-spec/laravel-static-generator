<?php

namespace App\Console\Commands;

use App\Contracts\HtmlGeneratorInterface;
use App\Contracts\SiteRepositoryInterface;
use Illuminate\Console\Command;

class SiteGenerateCommand extends Command
{
    protected $signature = 'site:generate {site_id : The ID of the site to generate}';
    protected $description = 'Generate HTML for all pages of a site';

    public function __construct(
        private HtmlGeneratorInterface $generator,
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

        $this->info("Generating HTML for site: {$site->name} ({$site->domain})");

        $result = $this->generator->generateSite($site);

        if ($result['success']) {
            $this->info("Successfully generated {$result['files_count']} files");
            return self::SUCCESS;
        } else {
            $this->error('Generation completed with errors:');
            foreach ($result['errors'] as $error) {
                $this->error("  - Page {$error['slug']}: {$error['error']}");
            }
            return self::FAILURE;
        }
    }
}
