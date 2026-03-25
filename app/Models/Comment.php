<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Comment extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'own',
        'user_id',
        'parent_id',
        'name',
        'presence',
        'comment',
        'is_admin',
        'gif_url',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'presence' => 'boolean',
            'is_admin' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Comment $comment) {
            $comment->uuid = $comment->uuid ?: Str::uuid()->toString();
            $comment->own = $comment->own ?: Str::random(64);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function toApiResponse(): array
    {
        return [
            'uuid' => $this->uuid,
            'own' => $this->own,
            'name' => $this->name,
            'presence' => $this->presence,
            'comment' => $this->comment,
            'created_at' => $this->created_at->toISOString(),
            'is_admin' => $this->is_admin,
            'is_parent' => $this->parent_id === null,
            'gif_url' => $this->gif_url,
            'ip' => $this->ip,
            'user_agent' => $this->user_agent,
            'like_count' => $this->likes_count ?? $this->likes()->count(),
            'comments' => $this->relationLoaded('replies')
                ? $this->replies->map->toApiResponse()->values()->toArray()
                : [],
        ];
    }
}
