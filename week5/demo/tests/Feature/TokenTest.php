<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email): User
    {
        return User::create([
            'name'     => '用户',
            'email'    => $email,
            'password' => 'secret123',
        ]);
    }

    private function token(string $email): string
    {
        return $this->postJson('/api/login', [
            'email'    => $email,
            'password' => 'secret123',
        ])->json('token');
    }

    private function refreshAuth(): void
    {
        $this->app['auth']->forgetGuards();
    }

    public function test_token_grants_access_to_profile(): void
    {
        $this->user('a@test.com');

        $this->withHeader('Authorization', 'Bearer ' . $this->token('a@test.com'))
            ->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('email', 'a@test.com');
    }

    public function test_token_is_stored_hashed(): void
    {
        $user  = $this->user('b@test.com');
        $plain = $this->token('b@test.com');

        [$id, $secret] = explode('|', $plain, 2);

        $stored = \DB::table('personal_access_tokens')->where('id', $id)->value('token');

        $this->assertNotSame($secret, $stored);                  // 不是明文
        $this->assertSame(hash('sha256', $secret), $stored);     // 是 sha256
        $this->assertSame($user->id, (int) \DB::table('personal_access_tokens')->where('id', $id)->value('tokenable_id'));
    }

    public function test_logout_revokes_token_immediately(): void
    {
        $this->user('c@test.com');
        $token = $this->token('c@test.com');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout')
            ->assertOk();

        $this->refreshAuth();  //丢掉上一次请求的认证状态

        // 同一个 token 再用一次 → 必须 401
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/profile')
            ->assertUnauthorized();

        $this->assertSame(0, \DB::table('personal_access_tokens')->count());
    }

    public function test_each_user_only_sees_own_profile(): void
    {
        $this->user('d@test.com');
        $this->user('e@test.com');

        $this->withHeader('Authorization', 'Bearer ' . $this->token('d@test.com'))
            ->getJson('/api/profile')
            ->assertJsonPath('email', 'd@test.com')
            ->assertJsonMissingPath('password');

        $this->refreshAuth();                      // ← 换身份前必须清

        $this->withHeader('Authorization', 'Bearer ' . $this->token('e@test.com'))
            ->getJson('/api/profile')
            ->assertJsonPath('email', 'e@test.com');
    }

    public function test_authenticated_user_can_list_users(): void
    {
        $this->user('list@test.com');

        $this->withHeader('Authorization', 'Bearer ' . $this->token('list@test.com'))
            ->getJson('/api/users')
            ->assertOk();
    }

    /* 登出全部token测试
    public function test_logout_all_revokes_every_token(): void
    {
        $user = $this->user('multi@test.com');
        $user->createToken('phone');
        $user->createToken('laptop');

        $this->withHeader('Authorization', 'Bearer ' . $this->token('multi@test.com'))
            ->postJson('/api/logout-all')
            ->assertOk();

        $this->assertSame(0, $user->tokens()->count());
    }
        */
}
