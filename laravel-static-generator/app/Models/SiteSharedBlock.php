<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSharedBlock extends Model
{
    protected $fillable = [
        'site_id',
        'locale',
        'menu_html',
        'mobile_menu_html',
        'footer_html',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
