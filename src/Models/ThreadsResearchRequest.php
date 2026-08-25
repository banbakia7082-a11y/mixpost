<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ThreadsResearchRequest extends Model
{
    protected $table = 'mixpost_threads_research_requests';

    protected $fillable = [
        'topic', 'keywords', 'posts_per_account', 'candidate_limit', 'frequency',
        'status', 'attempts', 'last_run_at', 'next_run_at', 'last_error',
    ];

    protected $casts = [
        'keywords' => 'array',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ThreadsResearchRequest $request) {
            $request->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
