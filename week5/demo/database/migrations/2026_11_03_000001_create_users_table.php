<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Week 2 是手工建的表，MySQL 里已经存在 → 跳过，只记一笔迁移历史
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
        });
    }

    public function down(): void
    {
        // 这张历史表不是迁移创建的，不在 down 里 drop，避免误删真实数据
    }
};
