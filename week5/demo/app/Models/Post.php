<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    protected $fillable = ['title', 'body', 'status'];      // ← 注意：没有 user_id

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
