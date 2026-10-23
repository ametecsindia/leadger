<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * One company (tenant) in the central database: its host, its own database and its status.
 * Always read through the central "mysql" connection (TenantSetup::on('mysql') / App\Support\Tenancy).
 * db_password is stored encrypted with APP_KEY (keep APP_KEY unchanged, or the company databases are unreachable).
 */
class TenantSetup extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_PROVISIONING = 'provisioning';
    public const STATUS_FAILED = 'failed';

    protected $connection = 'mysql';
    protected $table = 'tenants';

    protected $fillable = [
        'name', 'slug', 'subdomain', 'db_host', 'db_name', 'db_user', 'db_password', 'status', 'plan', 'max_users',
        'expires_at', 'provisioning_status', 'provisioning_log', 'app_version',
        'last_migrated_at', 'last_migration_status', 'last_migration_error',
    ];

    protected $hidden = ['db_password'];

    protected $casts = [
        'expires_at' => 'date',
        'last_migrated_at' => 'datetime',
        'max_users' => 'integer',
    ];

    protected static function booted(): void
    {
        // the host -> company lookup is cached: a change (status, database) applies at once
        $forget = function (self $t) {
            foreach (array_unique([(string)$t->getOriginal('subdomain'), (string)$t->subdomain]) as $host) {
                if ($host !== '') Cache::store('tenancy')->forget(\App\Support\Tenancy::lookupCacheKey($host));
            }
        };
        static::saved($forget);
        static::deleted($forget);
    }

    /** Decrypted; a password saved before encryption (plain text) is returned as it is. */
    public function getDbPasswordAttribute($value): ?string
    {
        if ($value === null || $value === '') return $value;
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return $value;
        }
    }

    public function setDbPasswordAttribute($value): void
    {
        $this->attributes['db_password'] = ($value === null || $value === '') ? $value : Crypt::encryptString((string)$value);
    }

    /** Usable: active (rows made before the status column have no status and count as active). */
    public function isActive(): bool
    {
        return in_array($this->status ?? self::STATUS_ACTIVE, [self::STATUS_ACTIVE, null, ''], true);
    }

    public function scopeActive($q)
    {
        return $q->where(fn ($w) => $w->whereNull('status')->orWhere('status', self::STATUS_ACTIVE));
    }

    /** Folder / cache name of this company: its slug, else derived from the host. */
    public function storageKey(): string
    {
        $key = $this->slug ?: preg_replace('/[^a-z0-9]+/', '-', strtolower((string)$this->subdomain));
        return trim($key, '-') ?: 'tenant-' . $this->id;
    }
}
