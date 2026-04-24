<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    protected $fillable = [
        'site_id',
        'path',
        'webp_path',
        'alt',
        'title',
        'width',
        'height',
        'size',
        'mime_type',
    ];
    
    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return route('admin.media.serve', [
            'siteId' => $this->site_id,
            'path' => str_replace("{$this->site_id}/", '', $this->path)
        ]);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
