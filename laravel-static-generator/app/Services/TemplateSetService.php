<?php

namespace App\Services;

use App\Models\TemplateSet;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TemplateSetService
{
    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return TemplateSet::all();
    }

    public function getById(int $id): ?TemplateSet
    {
        return TemplateSet::find($id);
    }

    public function getBySlug(string $slug): ?TemplateSet
    {
        return TemplateSet::where('slug', $slug)->first();
    }

    public function create(array $data): TemplateSet
    {
        $slug = $data['slug'] ?? Str::slug($data['name']);
        
        $targetPath = resource_path("views/templates/{$slug}");
        
        if (File::exists($targetPath)) {
            throw new \Exception("Template set '{$slug}' already exists");
        }

        $sourceTemplate = $data['source_template'] ?? 'base';
        $sourcePath = resource_path("views/templates/{$sourceTemplate}");
        
        if (!File::exists($sourcePath)) {
            throw new \Exception("Source template '{$sourceTemplate}' not found");
        }

        File::copyDirectory($sourcePath, $targetPath);

        return TemplateSet::create([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'path' => "templates/{$slug}",
            'is_active' => true,
        ]);
    }

    public function update(TemplateSet $templateSet, array $data): TemplateSet
    {
        if (isset($data['name'])) {
            $templateSet->name = $data['name'];
        }
        
        if (isset($data['description'])) {
            $templateSet->description = $data['description'];
        }
        
        if (isset($data['is_active'])) {
            $templateSet->is_active = $data['is_active'];
        }

        $templateSet->save();

        return $templateSet;
    }

    public function delete(TemplateSet $templateSet): bool
    {
        $path = $templateSet->getAbsolutePath();
        
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }

        return $templateSet->delete();
    }

    public function clone(TemplateSet $templateSet, string $newName): TemplateSet
    {
        $newSlug = Str::slug($newName);
        
        $sourcePath = $templateSet->getAbsolutePath();
        $targetPath = resource_path("views/templates/{$newSlug}");
        
        if (File::exists($targetPath)) {
            throw new \Exception("Template set '{$newSlug}' already exists");
        }

        File::copyDirectory($sourcePath, $targetPath);

        return TemplateSet::create([
            'name' => $newName,
            'slug' => $newSlug,
            'description' => $templateSet->description,
            'path' => "templates/{$newSlug}",
            'is_active' => true,
        ]);
    }

    public function validate(TemplateSet $templateSet): array
    {
        $requiredDirs = ['layouts', 'components', 'pages'];
        $requiredFiles = [
            'layouts/main.blade.php',
            'pages/default.blade.php',
        ];

        $missingDirs = [];
        $missingFiles = [];

        foreach ($requiredDirs as $dir) {
            $dirPath = $templateSet->getAbsolutePath() . '/' . $dir;
            if (!File::exists($dirPath)) {
                $missingDirs[] = $dir;
            }
        }

        foreach ($requiredFiles as $file) {
            $filePath = $templateSet->getAbsolutePath() . '/' . $file;
            if (!File::exists($filePath)) {
                $missingFiles[] = $file;
            }
        }

        return [
            'valid' => empty($missingDirs) && empty($missingFiles),
            'missing_dirs' => $missingDirs,
            'missing_files' => $missingFiles,
        ];
    }

    public function getComponents(TemplateSet $templateSet): array
    {
        $componentsPath = $templateSet->getAbsolutePath() . '/components';
        
        if (!File::exists($componentsPath)) {
            return [];
        }

        $components = [];
        foreach (File::files($componentsPath) as $file) {
            if (\Illuminate\Support\Str::endsWith($file->getFilename(), '.blade.php')) {
                $name = basename($file->getFilename(), '.blade.php');
                $components[$name] = $file->getFilename();
            }
        }

        return $components;
    }

    public static function getBuiltInTemplates(): array
    {
        $basePath = resource_path('views/templates');
        $templates = [];
        
        if (File::exists($basePath)) {
            foreach (File::directories($basePath) as $dir) {
                $templates[] = basename($dir);
            }
        }
        
        return $templates;
    }
}
