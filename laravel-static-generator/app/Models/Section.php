<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Section extends Model
{
    protected $fillable = [
        'page_id',
        'type',
        'module',
        'module_key',
        'heading',
        'subheading',
        'description',
        'content',
        'raw_html',
        'class',
        'identifier',
        'settings',
        'order',
    ];

    protected $casts = [
        'content' => 'array',
        'settings' => 'array',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
