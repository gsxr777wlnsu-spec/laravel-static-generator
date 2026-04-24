<?php

namespace App\Contracts;

use App\Models\Section;
use Illuminate\Database\Eloquent\Collection;

interface SectionRepositoryInterface
{
    public function create(array $data): Section;
    public function update(Section $section, array $data): Section;
    public function delete(Section $section): bool;
    public function reorder(int $pageId, array $order): void;
}
