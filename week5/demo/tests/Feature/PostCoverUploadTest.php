<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostCoverUploadTest extends TestCase
{
    use RefreshDatabase;

    private function author(string $email = 'a@test.com'): array
    {
        $user = User::create(['name' => '作者', 'email' => $email, 'password' => 'secret123']);
        $post = $user->posts()->create(['title' => '标题', 'body' => '正文']);

        $token = $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'secret123',
        ])->json('data.token');

        return [$user, $post, $token];
    }

    public function test_author_can_upload_cover(): void
    {
        Storage::fake('public');
        [, $post, $token] = $this->author();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/posts/{$post->id}/cover", [
                'cover' => UploadedFile::fake()->image('cover.jpg', 800, 600),
            ])
            ->assertOk()
            ->assertJsonPath('message', '封面已更新');

        $path = $post->fresh()->cover_path;

        $this->assertNotNull($path);
        $this->assertStringStartsWith("covers/{$post->id}/", $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_other_user_cannot_upload_cover(): void
    {
        Storage::fake('public');
        [, $post] = $this->author('a@test.com');

        User::create(['name' => '别人', 'email' => 'b@test.com', 'password' => 'secret123']);
        $token = $this->postJson('/api/login', [
            'email' => 'b@test.com',
            'password' => 'secret123',
        ])->json('data.token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/posts/{$post->id}/cover", [
                'cover' => UploadedFile::fake()->image('cover.jpg'),
            ])
            ->assertForbidden();

        $this->assertNull($post->fresh()->cover_path);
        Storage::disk('public')->assertDirectoryEmpty("covers/{$post->id}");
    }

    public function test_non_image_is_rejected(): void
    {
        Storage::fake('public');
        [, $post, $token] = $this->author();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/posts/{$post->id}/cover", [
                'cover' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cover']);

        Storage::disk('public')->assertDirectoryEmpty("covers/{$post->id}");
    }

    public function test_replacing_cover_deletes_old_file(): void
    {
        Storage::fake('public');
        [, $post, $token] = $this->author();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/posts/{$post->id}/cover", [
                'cover' => UploadedFile::fake()->image('a.jpg'),
            ])->assertOk();

        $old = $post->fresh()->cover_path;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/posts/{$post->id}/cover", [
                'cover' => UploadedFile::fake()->image('b.jpg'),
            ])->assertOk();

        $new = $post->fresh()->cover_path;

        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);
    }

    public function test_deleting_post_removes_cover_file(): void
    {
        Storage::fake('public');
        [, $post, $token] = $this->author();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/posts/{$post->id}/cover", ['cover' => UploadedFile::fake()->image('c.png')])
            ->assertOk();

        $path = $post->fresh()->cover_path;
        Storage::disk('public')->assertExists($path);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/posts/{$post->id}")
            ->assertOk();

        Storage::disk('public')->assertMissing($path);      // 文件跟着没了
    }

    public function test_deleting_user_removes_their_post_covers(): void
    {
        Storage::fake('public');
        [$author, $post] = $this->author('a@test.com');

        $token = $this->postJson('/api/login', ['email' => 'a@test.com', 'password' => 'secret123'])
            ->json('data.token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/posts/{$post->id}/cover", ['cover' => UploadedFile::fake()->image('c.png')])
            ->assertOk();

        $path = $post->fresh()->cover_path;

        // 管理员删除这个用户
        $admin = User::create(['name' => 'admin', 'email' => 'admin@test.com', 'password' => 'secret123']);
        $admin->forceFill(['is_admin' => true])->save();
        $adminToken = $this->postJson('/api/login', ['email' => 'admin@test.com', 'password' => 'secret123'])
            ->json('data.token');

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->deleteJson("/api/users/{$author->id}")
            ->assertOk();

        $this->assertDatabaseCount('posts', 0);
        Storage::disk('public')->assertMissing($path);       // 文件也必须没了
    }
}
