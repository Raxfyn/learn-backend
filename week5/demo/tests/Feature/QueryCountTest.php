<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QueryCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_list_does_not_trigger_n_plus_one(): void
    {
        $users = collect(range(1, 5))->map(function ($i) {
            $user = User::create([
                'name' => "作者{$i}",
                'email' => "u{$i}@test.com",
                'password' => 'secret123',
            ]);
            $user->posts()->create(['title' => "文章{$i}", 'body' => '内容']);

            return $user;
        });

        $token = $this->postJson('/api/login', [
            'email' => 'u1@test.com',
            'password' => 'secret123',
        ])->json('data.token');

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/posts')
            ->assertOk()
            ->assertJsonCount(5, 'data');

        $queries = collect(DB::getQueryLog())->pluck('query');

        // 关键断言：posts 表只查 1 次，users 表只查 1 次
        $this->assertSame(2, $queries->filter(fn($q) => str_contains($q, 'from "posts"'))->count());
        $this->assertLessThanOrEqual(3, $queries->filter(fn($q) => str_contains($q, 'from "users"'))->count());
    }
}
