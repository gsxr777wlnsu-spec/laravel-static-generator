<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deployment extends Model
{
    protected $fillable = [
        'site_id',
        'status',
        'started_at',
        'completed_at',
        'duration',
        'files_count',
        'log',
        'error_message',
        'deployed_by',
        'sftp_host',
        'remote_path',
        'backup_path',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
