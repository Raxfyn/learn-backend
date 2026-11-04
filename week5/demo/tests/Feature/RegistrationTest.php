<?php

namespace Tests\Feature;

use App\Events\UserRegistered;
use App\Jobs\SendWelcomeEmail;
use App\Listeners\SendWelcomeEmailListener;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_dispatches_user_registered_event(): void
    {
        Event::fake([UserRegistered::class]);      // 只 fake 这一个事件

        $response = $this->postJson('/api/users', [
            'name'  => '张三',
            'email' => 'zhangsan@test.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('email', 'zhangsan@test.com');

        $this->assertDatabaseHas('users', [
            'name'  => '张三',
            'email' => 'zhangsan@test.com',
        ]);

        Event::assertDispatched(UserRegistered::class, function (UserRegistered $event) {
            return $event->user->email === 'zhangsan@test.com';
        });
    }

    public function test_listener_dispatches_welcome_job(): void
    {
        Queue::fake();

        $user = User::create(['name' => '李四', 'email' => 'lisi@test.com']);

        (new SendWelcomeEmailListener())->handle(new UserRegistered($user));

        Queue::assertPushed(SendWelcomeEmail::class, function (SendWelcomeEmail $job) use ($user) {
            return $job->user->is($user);      // is() 比的是主键
        });
    }
}
