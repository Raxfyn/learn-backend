<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiContractTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email): User
    {
        return User::create(['name' => '张三', 'email' => $email, 'password' => 'secret123']);
    }

    private function token(string $email): string
    {
        return $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'secret123',
        ])->json('data.token');
    }

    public function test_login_response_has_unified_shape(): void
    {
        $this->user('a@test.com');

        $response = $this->postJson('/api/login', [
            'email' => 'a@test.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['token', 'token_type'], 'message'])
            ->assertJsonPath('message', '登录成功');
    }

    public function test_error_response_has_unified_shape(): void
    {
        $this->postJson('/api/login', ['email' => 'no@test.com', 'password' => 'x'])
            ->assertStatus(401)
            ->assertJsonStructure(['data', 'message'])
            ->assertJsonPath('data', null);
    }

    public function test_chinese_is_not_escaped(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => '张三',
            'email' => 'zh@test.com',
            'password' => 'secret123',
        ]);

        $this->assertStringContainsString('张三', $response->getContent());   // 不是 \u5f20\u4e09
    }

    public function test_user_list_is_paginated(): void
    {
        foreach (range(1, 3) as $i) {
            $this->user("u{$i}@test.com");
        }

        $this->withHeader('Authorization', 'Bearer ' . $this->token('u1@test.com'))
            ->getJson('/api/users?per_page=2')
            ->assertOk()
            ->assertJsonStructure([
                'data'    => [['id', 'name', 'email', 'is_admin']],
                'meta'    => ['current_page', 'per_page', 'total', 'last_page'],
                'message',
            ])
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonCount(2, 'data');
    }
}
