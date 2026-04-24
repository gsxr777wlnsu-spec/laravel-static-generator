<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    protected $fillable = [
        'site_id',
        'slug',
        'title',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical',
        'og_data',
        'json_ld',
        'status',
        'locale',
        'template_key',
        'parent_page_id',
    ];

    protected $casts = [
        'og_data' => 'array',
        'json_ld' => 'array',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('order');
    }

    public function languageVersions(): HasMany
    {
        return $this->hasMany(Page::class, 'parent_page_id');
    }

    public function parentPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_page_id');
    }
}
