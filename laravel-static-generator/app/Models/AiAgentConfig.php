<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentConfig extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'api_key',
        'api_base_url',
        'model_name',
        'temperature',
        'tone',
        'max_tokens',
        'top_p',
        'frequency_penalty',
        'presence_penalty',
        'allowed_paths',
        'allowed_sites',
        'is_active',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'allowed_paths' => 'array',
        'allowed_sites' => 'array',
        'is_active' => 'boolean',
        'temperature' => 'float',
        'top_p' => 'float',
        'frequency_penalty' => 'float',
        'presence_penalty' => 'float',
        'max_tokens' => 'integer',
    ];

    protected $hidden = [
        'api_key',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPathAllowed(string $absolutePath): bool
    {
        $allowedPaths = is_array($this->allowed_paths) ? $this->allowed_paths : [];

        if ($allowedPaths === []) {
            return true;
        }

        $target = str_replace('\\', '/', realpath($absolutePath) ?: $absolutePath);

        foreach ($allowedPaths as $allowed) {
            $allowed = trim((string) $allowed);
            if ($allowed === '') {
                continue;
            }

            $normalized = str_replace('\\', '/', realpath($allowed) ?: $allowed);

            // Support wildcard patterns such as "/var/www/*".
            if (strpbrk($normalized, '*?[]') !== false) {
                if (fnmatch($normalized, $target)) {
                    return true;
                }

                continue;
            }

            if (str_starts_with($target, rtrim($normalized, '/') . '/')
                || $target === rtrim($normalized, '/')
            ) {
                return true;
            }
        }

        return false;
    }

    public function isSiteAllowed(int $siteId): bool
    {
        $allowedSites = is_array($this->allowed_sites) ? $this->allowed_sites : [];
        if ($allowedSites === []) {
            return true;
        }

        return in_array($siteId, array_map('intval', $allowedSites), true);
    }
}
