<?php

namespace App\Contracts;

use App\Models\Section;

interface SectionServiceInterface
{
    public function add(int $pageId, string $type, array $content, ?int $order = null): Section;
    public function update(Section $section, array $data): Section;
    public function delete(Section $section): bool;
    public function reorder(int $pageId, array $order): void;
    public function validateSectionType(string $type, array $content): array;
}
