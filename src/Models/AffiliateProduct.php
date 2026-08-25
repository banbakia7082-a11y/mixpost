<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AffiliateProduct extends Model
{
    protected $table = 'mixpost_affiliate_products';
    protected $fillable = ['name', 'category', 'network', 'affiliate_url', 'actual_scene', 'benefit', 'drawback', 'active'];
    protected $casts = ['active' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(fn (AffiliateProduct $product) => $product->uuid ??= (string) Str::uuid());
    }

    public function drafts(): HasMany
    {
        return $this->hasMany(AffiliateDraft::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
