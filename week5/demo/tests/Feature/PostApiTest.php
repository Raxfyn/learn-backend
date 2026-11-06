<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, bool $admin = false): User
    {
        $user = User::create(['name' => '作者', 'email' => $email, 'password' => 'secret123']);

        if ($admin) {
            $user->forceFill(['is_admin' => true])->save();
        }

        return $user;
    }

    private function token(string $email): string
    {
        return $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'secret123',
        ])->json('data.token');
    }

    public function test_user_can_create_post(): void
    {
        $user = $this->user('a@test.com');

        $this->withHeader('Authorization', 'Bearer ' . $this->token('a@test.com'))
            ->postJson('/api/posts', [
                'title'   => '标题',
                'body'    => '正文',
                'user_id' => 999999,        // 试图伪造作者
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', '标题');

        $this->assertDatabaseHas('posts', ['user_id' => $user->id]);
    }

    public function test_author_can_update_own_post(): void
    {
        $author = $this->user('a@test.com');
        $post   = $author->posts()->create(['title' => '旧', 'body' => '正文']);

        $this->withHeader('Authorization', 'Bearer ' . $this->token('a@test.com'))
            ->patchJson("/api/posts/{$post->id}", ['title' => '新'])
            ->assertOk()
            ->assertJsonPath('data.title', '新');
    }

    public function test_other_user_cannot_update_post(): void
    {
        $author = $this->user('a@test.com');
        $this->user('b@test.com');
        $post = $author->posts()->create(['title' => '旧', 'body' => '正文']);

        $this->withHeader('Authorization', 'Bearer ' . $this->token('b@test.com'))
            ->patchJson("/api/posts/{$post->id}", ['title' => '被改了'])
            ->assertForbidden();

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => '旧']);
    }

    public function test_admin_can_update_any_post(): void
    {
        $author = $this->user('a@test.com');
        $this->user('admin@test.com', true);
        $post = $author->posts()->create(['title' => '旧', 'body' => '正文']);

        $this->withHeader('Authorization', 'Bearer ' . $this->token('admin@test.com'))
            ->patchJson("/api/posts/{$post->id}", ['title' => '管理员改的'])
            ->assertOk();
    }

    public function test_other_user_cannot_delete_post(): void
    {
        $author = $this->user('a@test.com');
        $this->user('b@test.com');
        $post = $author->posts()->create(['title' => 't', 'body' => 'b']);

        $this->withHeader('Authorization', 'Bearer ' . $this->token('b@test.com'))
            ->deleteJson("/api/posts/{$post->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_nested_list_only_returns_that_users_posts(): void
    {
        $alice = $this->user('a@test.com');
        $bob   = $this->user('b@test.com');
        $alice->posts()->create(['title' => 'alice 的', 'body' => 'b']);
        $bob->posts()->createMany([
            ['title' => 'bob 的1', 'body' => 'b'],
            ['title' => 'bob 的2', 'body' => 'b'],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->token('a@test.com'))
            ->getJson("/api/users/{$alice->id}/posts")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'alice 的');
    }

    public function test_guest_cannot_create_post(): void
    {
        $this->postJson('/api/posts', ['title' => 'x', 'body' => 'y'])
            ->assertUnauthorized();
    }
}
