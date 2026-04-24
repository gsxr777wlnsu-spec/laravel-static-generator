<?php

namespace App\Contracts;

use App\Models\Page;
use App\Models\Site;

interface HtmlGeneratorInterface
{
    public function generatePage(Page $page): string;
    public function generateSite(Site $site, ?callable $onProgress = null): array;
    public function generateSitemap(Site $site): string;
    public function generateRobotsTxt(Site $site): string;
    public function generatePreview(Page $page): array;
    public function cleanupExpiredPreviews(): int;
}
