<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Like extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'uuid',
        'comment_id',
        'ip',
    ];

    protected static function booted(): void
    {
        static::creating(function (Like $like) {
            $like->uuid = $like->uuid ?: Str::uuid()->toString();
        });
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }
}
