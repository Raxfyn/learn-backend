<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    use HasApiTokens;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password'];   //序列化时永不出现

    public $timestamps = false;

    protected function casts(): array   //赋值时自动哈希
    {
        return ['password' => 'hashed', 'is_admin' => 'boolean'];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
