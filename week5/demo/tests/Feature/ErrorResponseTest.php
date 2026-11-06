<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorResponseTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, bool $admin = false): User
    {
        $user = User::create(['name' => '用户', 'email' => $email, 'password' => 'secret123']);

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

    public function test_unauthenticated_returns_unified_401(): void
    {
        $this->getJson('/api/profile')
            ->assertStatus(401)
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', '未认证或 token 已失效');
    }

    public function test_forbidden_returns_unified_403(): void
    {
        $this->user('alice@test.com');
        $bob = $this->user('bob@test.com');

        $this->withHeader('Authorization', 'Bearer ' . $this->token('alice@test.com'))
            ->patchJson("/api/users/{$bob->id}", ['name' => 'x'])
            ->assertStatus(403)
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', '无权限执行该操作');
    }

    public function test_missing_resource_returns_unified_404(): void
    {
        $this->user('a@test.com');

        $this->withHeader('Authorization', 'Bearer ' . $this->token('a@test.com'))
            ->patchJson('/api/users/999999', ['name' => 'x'])
            ->assertStatus(404)
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', '资源不存在');
    }

    public function test_validation_error_returns_unified_422(): void
    {
        $this->postJson('/api/users', [])
            ->assertStatus(422)
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', '参数校验失败')
            ->assertJsonStructure(['data', 'message', 'errors'])
            ->assertJsonValidationErrors(['name']);
    }
}
