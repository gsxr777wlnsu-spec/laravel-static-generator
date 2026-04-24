<?php

namespace App\Repositories;

use App\Contracts\SectionRepositoryInterface;
use App\Models\Section;

class SectionRepository implements SectionRepositoryInterface
{
    public function create(array $data): Section
    {
        if (!isset($data['order'])) {
            $maxOrder = Section::where('page_id', $data['page_id'])->max('order');
            $data['order'] = ($maxOrder ?? -1) + 1;
        }
        
        return Section::create($data);
    }

    public function update(Section $section, array $data): Section
    {
        $section->update($data);
        return $section->fresh();
    }

    public function delete(Section $section): bool
    {
        $pageId = $section->page_id;
        $deleted = $section->delete();
        
        if ($deleted) {
            $this->recalculateOrder($pageId);
        }
        
        return $deleted;
    }

    public function reorder(int $pageId, array $order): void
    {
        foreach ($order as $index => $sectionId) {
            Section::where('id', $sectionId)
                ->where('page_id', $pageId)
                ->update(['order' => $index]);
        }
    }

    private function recalculateOrder(int $pageId): void
    {
        $sections = Section::where('page_id', $pageId)
            ->orderBy('order')
            ->get();
        
        foreach ($sections as $index => $section) {
            $section->update(['order' => $index]);
        }
    }
}
