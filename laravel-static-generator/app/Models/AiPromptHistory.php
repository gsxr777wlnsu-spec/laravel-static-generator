<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPromptHistory extends Model
{
    protected $fillable = ['template_set', 'page_key', 'module_key', 'locale', 'field_key', 'scope_hash', 'prompt', 'prompt_hash', 'is_favorite', 'last_used_at'];

    protected $casts = ['is_favorite' => 'boolean', 'last_used_at' => 'datetime'];
}
