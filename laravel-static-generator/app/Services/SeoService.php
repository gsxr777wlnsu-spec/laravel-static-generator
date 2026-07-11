<?php

namespace App\Services;

use App\Contracts\SeoServiceInterface;
use App\Models\Page;
use App\Models\Site;

class SeoService implements SeoServiceInterface
{
    private const MAX_TITLE_LENGTH = 60;
    private const MAX_DESCRIPTION_LENGTH = 160;

    public function validateMetaTitle(string $title): array
    {
        $length = mb_strlen($title);
        
        return [
            'valid' => $length <= self::MAX_TITLE_LENGTH,
            'length' => $length,
            'max_length' => self::MAX_TITLE_LENGTH,
            'message' => $length > self::MAX_TITLE_LENGTH 
                ? "Meta title exceeds recommended length of " . self::MAX_TITLE_LENGTH . " characters"
                : null
        ];
    }

    public function validateMetaDescription(string $description): array
    {
        $length = mb_strlen($description);
        
        return [
            'valid' => $length <= self::MAX_DESCRIPTION_LENGTH,
            'length' => $length,
            'max_length' => self::MAX_DESCRIPTION_LENGTH,
            'message' => $length > self::MAX_DESCRIPTION_LENGTH 
                ? "Meta description exceeds recommended length of " . self::MAX_DESCRIPTION_LENGTH . " characters"
                : null
        ];
    }

    public function generateMetaTitle(string $content): string
    {
        $title = strip_tags($content);
        $title = preg_replace('/\s+/', ' ', $title);
        $title = trim($title);
        
        if (mb_strlen($title) > self::MAX_TITLE_LENGTH) {
            $title = mb_substr($title, 0, self::MAX_TITLE_LENGTH - 3) . '...';
        }
        
        return $title;
    }

    public function generateMetaDescription(string $content): string
    {
        $description = strip_tags($content);
        $description = preg_replace('/\s+/', ' ', $description);
        $description = trim($description);
        
        if (mb_strlen($description) > self::MAX_DESCRIPTION_LENGTH) {
            $description = mb_substr($description, 0, self::MAX_DESCRIPTION_LENGTH - 3) . '...';
        }
        
        return $description;
    }

    public function checkDuplicateSlugs(Site $site, string $slug, ?int $excludePageId = null, ?string $locale = null): bool
    {
        $query = Page::where('site_id', $site->id)
            ->where('slug', $slug);
        
        if ($excludePageId) {
            $query->where('id', '!=', $excludePageId);
        }

        if ($locale !== null) {
            $query->where('locale', $locale);
        }
        
        return $query->exists();
    }
}
