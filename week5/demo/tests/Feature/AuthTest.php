<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;    //每个测试前跑一遍全部迁移（在内存库里）
    public function test_profile_requires_authentication(): void
    {
        $this->getJson('/api/profile')->assertUnauthorized();  //正确写法
        //$this->getJson('/api/profile')->assertOk();   // ← 故意写错，先看它红
    }
    public function test_profile_rejects_invalid_token(): void
    {
        $this->withHeader('Authorization', 'Bearer 这不是一个合法的token')
            ->getJson('/api/profile')
            ->assertUnauthorized();
    }

    public function test_users_list_requires_authentication(): void
    {
        $this->getJson('/api/users')->assertUnauthorized();
    }
}
