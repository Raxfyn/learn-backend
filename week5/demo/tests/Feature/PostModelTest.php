<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostModelTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email = 'u@test.com'): User
    {
        return User::create(['name' => '作者', 'email' => $email, 'password' => 'secret123']);
    }

    public function test_user_has_many_posts(): void
    {
        $user = $this->user();
        $user->posts()->create(['title' => '第一篇', 'body' => '内容']);

        $post = $user->posts()->first();

        $this->assertSame(1, $user->posts()->count());
        $this->assertTrue($post->user->is($user));
    }

    public function test_deleting_user_cascades_posts(): void
    {
        $user = $this->user();
        $user->posts()->create(['title' => '要被级联删掉', 'body' => '内容']);

        $user->delete();

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_post_requires_existing_user(): void
    {
        $this->expectException(QueryException::class);

        $post = new Post(['title' => '孤儿文章', 'body' => '内容']);
        $post->user_id = 999999;
        $post->save();
    }

    public function test_user_id_is_not_mass_assignable(): void
    {
        $user = $this->user();

        $user->posts()->create([
            'title'   => '尝试伪造作者',
            'body'    => '内容',
            'user_id' => 999999,
        ]);

        $this->assertDatabaseHas('posts', ['user_id' => $user->id]);
    }
}
