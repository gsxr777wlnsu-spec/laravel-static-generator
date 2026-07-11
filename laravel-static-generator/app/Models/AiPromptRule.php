<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPromptRule extends Model
{
    protected $fillable = [
        'template_set',
        'page_key',
        'field_key',
        'rule',
    ];
}
