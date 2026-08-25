<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AffiliatePost extends Model
{
    protected $table = 'mixpost_affiliate_posts';

    protected $fillable = [
        'platform',
        'threads_reference_account_id',
        'external_post_id',
        'post_url',
        'title',
        'content',
        'content_analysis',
        'analyzed_at',
        'analysis_version',
        'product_name',
        'product_category',
        'affiliate_network',
        'affiliate_url',
        'post_type',
        'image_type',
        'disclosure_present',
        'published_at',
    ];

    protected $casts = [
        'disclosure_present' => 'boolean',
        'published_at' => 'datetime',
        'content_analysis' => 'array',
        'analyzed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (AffiliatePost $post) {
            $post->uuid ??= (string) Str::uuid();
        });
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(AffiliatePostSnapshot::class);
    }

    public function referenceAccount(): BelongsTo
    {
        return $this->belongsTo(ThreadsReferenceAccount::class, 'threads_reference_account_id');
    }

    public function latestSnapshot()
    {
        return $this->hasOne(AffiliatePostSnapshot::class)->latestOfMany('captured_at');
    }
}
