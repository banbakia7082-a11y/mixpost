<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ThreadsReferenceAccount extends Model
{
    protected $table = 'mixpost_threads_reference_accounts';

    protected $fillable = ['handle', 'display_name', 'profile_url', 'active'];

    protected $casts = ['active' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (ThreadsReferenceAccount $account) {
            $account->uuid ??= (string) Str::uuid();
        });
    }

    public function posts(): HasMany
    {
        return $this->hasMany(AffiliatePost::class, 'threads_reference_account_id');
    }
}
