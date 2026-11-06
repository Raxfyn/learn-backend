<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $name, string $email, bool $admin = false): User
    {
        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => 'secret123',
        ]);

        if ($admin) {
            $user->forceFill(['is_admin' => true])->save();
        }

        return $user;
    }

    private function token(string $email): string
    {
        return $this->postJson('/api/login', [
            'email'    => $email,
            'password' => 'secret123',
        ])->json('data.token');
    }

    public function test_guest_cannot_delete(): void
    {
        $bob = $this->user('bob', 'bob@test.com');

        $this->deleteJson("/api/users/{$bob->id}")->assertUnauthorized();
    }

    public function test_user_cannot_delete_another_user(): void
    {
        $this->user('alice', 'alice@test.com');
        $bob = $this->user('bob', 'bob@test.com');

        $this->withHeader('Authorization', 'Bearer ' . $this->token('alice@test.com'))
            ->deleteJson("/api/users/{$bob->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $bob->id]);
    }

    public function test_user_cannot_delete_self(): void
    {
        $alice = $this->user('alice', 'alice@test.com');

        $this->withHeader('Authorization', 'Bearer ' . $this->token('alice@test.com'))
            ->deleteJson("/api/users/{$alice->id}")
            ->assertForbidden();               // 管理员也不能删自己，见 Policy::delete

        $this->assertDatabaseHas('users', ['id' => $alice->id]);
    }

    public function test_admin_can_delete_another_user(): void
    {
        $this->user('admin', 'admin@test.com', true);
        $bob = $this->user('bob', 'bob@test.com');
        $bob->createToken('phone');

        $this->withHeader('Authorization', 'Bearer ' . $this->token('admin@test.com'))
            ->deleteJson("/api/users/{$bob->id}")
            ->assertOK()
            ->assertJsonPath('message', '已删除');               // 现在是200+ message

        $this->assertDatabaseMissing('users', ['id' => $bob->id]);
        $this->assertSame(0, $bob->tokens()->count());   // 孤儿 token 已清理
    }
}
