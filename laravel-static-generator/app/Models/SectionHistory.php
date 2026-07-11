<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionHistory extends Model
{
    protected $fillable = [
        'section_id',
        'page_id',
        'type',
        'content',
        'order',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
