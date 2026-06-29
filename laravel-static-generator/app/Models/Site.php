<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'template_set',
        'output_path',
        'status',
        'locale',
        'default_locale',
        'menu_html',
        'mobile_menu_html',
        'footer_html',
        'sftp_host',
        'sftp_port',
        'sftp_username',
        'sftp_password',
        'sftp_private_key',
        'sftp_auth_method',
        'sftp_remote_path',
    ];

    protected $casts = [
        'sftp_port' => 'integer',
    ];

    protected $hidden = [
        'sftp_password',
        'sftp_private_key',
    ];

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    public function getSftpCredentials(): array
    {
        return [
            'host' => $this->sftp_host,
            'port' => $this->sftp_port,
            'username' => $this->sftp_username,
            'password' => $this->sftp_password,
            'private_key' => $this->sftp_private_key,
            'auth_method' => $this->sftp_auth_method,
            'remote_path' => $this->sftp_remote_path,
        ];
    }
}
