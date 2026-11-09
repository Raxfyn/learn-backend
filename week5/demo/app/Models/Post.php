<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    protected $fillable = ['title', 'body', 'status'];      // ← 注意：没有 user_id

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Post $post) {
            if ($post->cover_path) {
                Storage::disk('public')->delete($post->cover_path);
            }
        });
    }
}
