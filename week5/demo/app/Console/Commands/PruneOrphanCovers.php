<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Models\Post;
use Illuminate\Support\Facades\Storage;

class PruneOrphanCovers extends Command
{
    protected $signature   = 'covers:prune';
    protected $description = '删除数据库里已不存在的封面文件';

    public function handle(): int
    {
        $known = Post::whereNotNull('cover_path')->pluck('cover_path')->all();
        $disk  = Storage::disk('public');
        $deleted = 0;

        foreach ($disk->allFiles('covers') as $file) {
            if (! in_array($file, $known, true)) {
                $disk->delete($file);
                $deleted++;
            }
        }

        $this->info("清理孤儿文件: {$deleted} 个");

        return self::SUCCESS;
    }
}
