<?php

declare(strict_types=1);

namespace MyAds\Plugins\SocialAutoPoster\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SocialPosterLog extends Model
{
    protected $table = 'social_auto_poster_logs';

    protected $guarded = [];

    protected $casts = [
        'status_code' => 'integer',
        'published_at' => 'datetime',
        'response_data' => 'array',
    ];

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeByChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    public function scopeByContentType(Builder $query, string $type): Builder
    {
        return $query->where('content_type', $type);
    }
}
