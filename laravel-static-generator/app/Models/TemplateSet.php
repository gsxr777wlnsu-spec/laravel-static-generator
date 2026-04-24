<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'slug', 'description', 'path', 'is_active'])]
class TemplateSet extends Model
{
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function sites()
    {
        return $this->hasMany(Site::class, 'template_set', 'slug');
    }

    public function getAbsolutePath(): string
    {
        return resource_path("views/templates/{$this->slug}");
    }

    public function validateStructure(): array
    {
        $requiredFiles = [
            'layouts/main.blade.php',
            'components/text.blade.php',
        ];

        $missing = [];
        foreach ($requiredFiles as $file) {
            if (!File::exists($this->getAbsolutePath() . '/' . $file)) {
                $missing[] = $file;
            }
        }

        return [
            'valid' => empty($missing),
            'missing' => $missing,
        ];
    }

    public function getAvailableComponents(): array
    {
        $componentsPath = $this->getAbsolutePath() . '/components';
        
        if (!File::exists($componentsPath)) {
            return [];
        }

        $components = [];
        foreach (File::files($componentsPath) as $file) {
            if ($file->getExtension() === 'php' && str_ends_with($file->getFilename(), '.blade.php')) {
                $components[] = basename($file->getFilename(), '.blade.php');
            }
        }

        return $components;
    }

    public static function createFromDirectory(string $sourcePath, string $name, string $slug): self
    {
        $targetPath = resource_path("views/templates/{$slug}");
        
        if (File::exists($targetPath)) {
            throw new \Exception("Template set '{$slug}' already exists");
        }

        File::copyDirectory($sourcePath, $targetPath);

        return self::create([
            'name' => $name,
            'slug' => $slug,
            'path' => "templates/{$slug}",
            'is_active' => true,
        ]);
    }

    public function clone(string $newName, string $newSlug): self
    {
        $sourcePath = $this->getAbsolutePath();
        $targetPath = resource_path("views/templates/{$newSlug}");
        
        if (File::exists($targetPath)) {
            throw new \Exception("Template set '{$newSlug}' already exists");
        }

        File::copyDirectory($sourcePath, $targetPath);

        return self::create([
            'name' => $newName,
            'slug' => $newSlug,
            'path' => "templates/{$newSlug}",
            'description' => $this->description,
            'is_active' => true,
        ]);
    }

    public static function getAvailableTemplates(): array
    {
        $baseTemplatesPath = resource_path('views/templates');
        $templates = [];

        if (File::exists($baseTemplatesPath)) {
            foreach (File::directories($baseTemplatesPath) as $dir) {
                $templates[] = [
                    'slug' => basename($dir),
                    'name' => basename($dir),
                    'path' => resource_path('views/templates/' . basename($dir)),
                ];
            }
        }

        return $templates;
    }
}
