<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliatePostSnapshot extends Model
{
    protected $table = 'mixpost_affiliate_post_snapshots';

    protected $fillable = [
        'captured_at',
        'views',
        'reactions',
        'replies',
        'reposts',
        'quotes',
        'link_clicks',
        'sales',
        'revenue',
        'raw_metrics',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'revenue' => 'decimal:2',
        'raw_metrics' => 'array',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(AffiliatePost::class, 'affiliate_post_id');
    }
}
