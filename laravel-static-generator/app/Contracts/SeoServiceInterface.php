<?php

namespace App\Contracts;

use App\Models\Site;

interface SeoServiceInterface
{
    public function validateMetaTitle(string $title): array;
    public function validateMetaDescription(string $description): array;
    public function generateMetaTitle(string $content): string;
    public function generateMetaDescription(string $content): string;
    public function checkDuplicateSlugs(Site $site, string $slug, ?int $excludePageId = null, ?string $locale = null): bool;
}
