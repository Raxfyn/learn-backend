<?php

namespace Tests\Feature;

use App\Events\UserRegistered;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UserUpdateTest extends TestCase
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
            $user->forceFill(['is_admin' => true])->save(); //提权必须显式
        }

        return $user;
    }

    private function token(string $email): string
    {
        return $this->postJson('/api/login', [
            'email'    => $email,
            'password' => 'secret123',
        ])->json('token');
    }

    public function test_user_cannot_update_another_user(): void
    {
        $this->user('alice', 'alice@test.com');
        $bob = $this->user('bob', 'bob@test.com');

        $this->withHeader('Authorization', 'Bearer ' . $this->token('alice@test.com'))
            ->patchJson("/api/users/{$bob->id}", ['name' => '被 alice 改了'])
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $bob->id, 'name' => 'bob']);
    }

    public function test_registration_cannot_self_promote(): void
    {
        Event::fake([UserRegistered::class]);

        $this->postJson('/api/users', [
            'name'     => 'hacker',
            'email'    => 'hacker@test.com',
            'password' => 'secret123',
            'is_admin' => true,
        ])->assertCreated();

        $this->assertDatabaseHas('users', [
            'email'    => 'hacker@test.com',
            'is_admin' => false,
        ]);
    }

    public function test_user_can_update_own_profile(): void
    {
        $alice = $this->user('alice', 'alice@test.com');

        $this->withHeader('Authorization', 'Bearer ' . $this->token('alice@test.com'))
            ->patchJson("/api/users/{$alice->id}", ['name' => 'alice-new'])
            ->assertOk()
            ->assertJsonPath('name', 'alice-new');

        $this->assertDatabaseHas('users', ['id' => $alice->id, 'name' => 'alice-new']);
    }

    public function test_admin_can_update_another_user(): void
    {
        $admin = $this->user('admin', 'admin@test.com', true);
        $bob = $this->user('bob', 'bob@test.com');

        $this->assertTrue($admin->is_admin);    //先确认“真的是管理员”

        $this->withHeader('Authorization', 'Bearer ' . $this->token('admin@test.com'))
            ->patchJson("/api/users/{$bob->id}", ['name' => 'by-admin'])
            ->assertOk();

        $this->assertDatabaseHas('users', ['id' => $bob->id, 'name' => 'by-admin']);
    }

    public function test_guest_update_returns_401_not_403(): void
    {
        $bob = $this->user('bob', 'bob@test.com');

        $this->patchJson("/api/users/{$bob->id}", ['name' => 'x'])
            ->assertUnauthorized();                 // 401，不是 403
    }

    public function test_user_cannot_promote_self_via_patch(): void
    {
        $alice = $this->user('alice', 'alice@test.com');

        $this->withHeader('Authorization', 'Bearer ' . $this->token('alice@test.com'))
            ->patchJson("/api/users/{$alice->id}", [
                'name'     => 'alice',
                'is_admin' => true,                 // 试图自我提权
            ])->assertOk();

        $this->assertFalse($alice->fresh()->is_admin);   // ← 注意 fresh()
    }

    public function test_admin_cannot_promote_others_via_patch(): void
    {
        $this->user('admin', 'admin@test.com', true);
        $bob = $this->user('bob', 'bob@test.com');

        $this->withHeader('Authorization', 'Bearer ' . $this->token('admin@test.com'))
            ->patchJson("/api/users/{$bob->id}", ['name' => 'bob', 'is_admin' => true])
            ->assertOk();

        $this->assertFalse($bob->fresh()->is_admin);
    }
}
