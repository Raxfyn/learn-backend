<?php

namespace Tests\Feature;

use App\Events\UserRegistered;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EmailUniquenessTest extends TestCase
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

    public function test_duplicate_email_registration_returns_422(): void
    {
        Event::fake([UserRegistered::class]);
        $this->user('taken@test.com');

        $this->postJson('/api/users', [
            'name'     => '另一个',
            'email'    => 'taken@test.com',
            'password' => 'secret123',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_update_email_to_taken_email_returns_422(): void
    {
        $this->user('a@test.com');
        $bob = $this->user('b@test.com');

        $token = $this->postJson('/api/login', [
            'email' => 'b@test.com',
            'password' => 'secret123',
        ])->json('data.token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/users/{$bob->id}", ['email' => 'a@test.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_update_with_own_email_is_allowed(): void
    {
        $alice = $this->user('c@test.com');

        $token = $this->postJson('/api/login', [
            'email' => 'c@test.com',
            'password' => 'secret123',
        ])->json('data.token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/users/{$alice->id}", [
                'name'  => '改了名字',
                'email' => 'c@test.com',      // 自己的邮箱，不该报重复
            ])->assertOk();
    }

    public function test_database_rejects_duplicate_email(): void
    {
        $this->user('dup@test.com');

        $this->expectException(QueryException::class);   // 索引真的在，绕过应用层也插不进去

        User::create([
            'name'     => 'dup',
            'email'    => 'dup@test.com',
            'password' => 'secret123',
        ]);
    }
}
