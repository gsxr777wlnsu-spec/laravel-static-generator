<?php

namespace App\Services;

use App\Contracts\SectionServiceInterface;
use App\Contracts\SectionRepositoryInterface;
use App\Models\Section;

class SectionService implements SectionServiceInterface
{
    private const SECTION_TYPES = ['module', 'faq', 'hero', 'text', 'list', 'table', 'gallery', 'cta'];
    
    private const REQUIRED_FIELDS = [
        'module' => [],
        'faq' => ['question', 'answer'],
        'hero' => ['heading', 'image'],
        'text' => ['content'],
        'list' => ['items'],
        'table' => ['headers', 'rows'],
        'gallery' => ['images'],
        'cta' => ['text', 'link'],
    ];

    public function __construct(
        private SectionRepositoryInterface $repository
    ) {}

    public function add(int $pageId, string $type, array $content, ?int $order = null): Section
    {
        $validation = $this->validateSectionType($type, $content);
        
        if (!$validation['valid']) {
            throw new \InvalidArgumentException($validation['message']);
        }

        return $this->repository->create([
            'page_id' => $pageId,
            'type' => $type,
            'content' => $content,
            'order' => $order,
        ]);
    }

    public function update(Section $section, array $data): Section
    {
        if (isset($data['type']) && isset($data['content'])) {
            $validation = $this->validateSectionType($data['type'], $data['content']);
            
            if (!$validation['valid']) {
                throw new \InvalidArgumentException($validation['message']);
            }
        }

        return $this->repository->update($section, $data);
    }

    public function delete(Section $section): bool
    {
        return $this->repository->delete($section);
    }

    public function reorder(int $pageId, array $order): void
    {
        $this->repository->reorder($pageId, $order);
    }

    public function validateSectionType(string $type, array $content): array
    {
        if (!in_array($type, self::SECTION_TYPES)) {
            return [
                'valid' => false,
                'message' => "Invalid section type: {$type}. Allowed types: " . implode(', ', self::SECTION_TYPES)
            ];
        }

        $requiredFields = self::REQUIRED_FIELDS[$type] ?? [];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (!isset($content[$field]) || empty($content[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            return [
                'valid' => false,
                'message' => "Section type '{$type}' requires fields: " . implode(', ', $missingFields)
            ];
        }

        return ['valid' => true, 'message' => null];
    }
}
