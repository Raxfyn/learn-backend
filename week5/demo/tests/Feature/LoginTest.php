<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'name'     => '张三',
            'email'    => 'zhangsan@test.com',
            'password' => 'secret123',
        ], $attrs));
    }

    public function test_login_returns_token_for_valid_credentials(): void
    {
        $user = $this->makeUser();

        $response = $this->postJson('/api/login', [
            'email'    => 'zhangsan@test.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()->assertJsonStructure(['data' => ['token', 'token_type'], 'message']);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->makeUser();

        $this->postJson('/api/login', [
            'email'    => 'zhangsan@test.com',
            'password' => 'wrong-password',
        ])->assertStatus(401)->assertJsonMissingPath('data.token');
    }

    public function test_login_fails_with_unknown_email(): void
    {
        $this->postJson('/api/login', [
            'email'    => 'nobody@test.com',
            'password' => 'secret123',
        ])->assertStatus(401);
    }

    public function test_legacy_user_without_password_gets_401_not_500(): void
    {
        User::create(['name' => '老用户', 'email' => 'legacy@test.com']);   // password = NULL

        $this->postJson('/api/login', [
            'email'    => 'legacy@test.com',
            'password' => 'anything',
        ])->assertStatus(401);
    }

    public function test_login_requires_valid_payload(): void
    {
        $this->postJson('/api/login', ['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }
}
