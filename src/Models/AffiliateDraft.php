<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AffiliateDraft extends Model
{
    protected $table = 'mixpost_affiliate_drafts';
    protected $fillable = ['affiliate_product_id', 'parent_post', 'reply_post', 'angle', 'scores', 'evidence_post_ids', 'generation_reason', 'status'];
    protected $casts = ['scores' => 'array', 'evidence_post_ids' => 'array'];

    protected static function booted(): void
    {
        static::creating(fn (AffiliateDraft $draft) => $draft->uuid ??= (string) Str::uuid());
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(AffiliateProduct::class, 'affiliate_product_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
