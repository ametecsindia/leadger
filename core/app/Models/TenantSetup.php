<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * One company (tenant) of the companies list: its host and its own database. The table has exactly
 * id, subdomain, db_host, db_name, db_user, db_password, created_at, updated_at; every listed company is active.
 * Read through the companies list's own connection (getConnectionName: "mysql", or "tenancy" for tenants_leadger).
 * db_password: plain text, or encrypted with APP_KEY when TENANCY_ENCRYPT_PASSWORDS=true (then never change APP_KEY).
 */
class TenantSetup extends Model
{
    protected $table = 'tenants';

    /** The companies list's own connection (App\Support\Tenancy::registry()), never a company database. */
    public function getConnectionName()
    {
        return \App\Support\Tenancy::registry();
    }

    protected $fillable = ['subdomain', 'db_host', 'db_name', 'db_user', 'db_password'];

    protected $hidden = ['db_password'];

    protected static function booted(): void
    {
        // the host -> company lookup is cached: a change (address, database) applies at once
        $forget = function (self $t) {
            foreach (array_unique([(string)$t->getOriginal('subdomain'), (string)$t->subdomain]) as $host) {
                if ($host !== '') Cache::store('tenancy')->forget(\App\Support\Tenancy::lookupCacheKey($host));
            }
        };
        static::saved($forget);
        static::deleted($forget);
        // the copy database keeps a copy of a list that lives in another database: refresh it right away
        $copy = fn () => \App\Services\TenantsCopy::sync();
        static::saved($copy);
        static::deleted($copy);
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

    /** Encrypted only with TENANCY_ENCRYPT_PASSWORDS=true: another installation may read this table in plain text. */
    public function setDbPasswordAttribute($value): void
    {
        $this->attributes['db_password'] = ($value === null || $value === '' || !config('tenancy.encrypt_passwords'))
            ? $value : Crypt::encryptString((string)$value);
    }

    /** Short name of the company from its address (company-a.leadger.in -> company-a). */
    public function shortName(): string
    {
        return explode('.', (string)$this->subdomain)[0] ?: (string)$this->subdomain;
    }

    /** Folder / cache name of this company, from its address. */
    public function storageKey(): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower((string)$this->subdomain)), '-') ?: 'tenant-' . $this->id;
    }
}
