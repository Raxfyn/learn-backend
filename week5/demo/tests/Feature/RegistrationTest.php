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
use Illuminate\Support\Facades\Hash;

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

    public function test_register_hashes_password_and_hides_it(): void
    {
        Event::fake([UserRegistered::class]);

        $response = $this->postJson('/api/users', [
            'name'      => '密码用户',
            'email'     => 'pwd@test.com',
            'password'  => 'secret123',
        ]);

        // ① 响应体里不能出现 password 字段
        $response->assertCreated()->assertJsonMissingPath('password');

        $user = User::where('email', 'pwd@test.com')->firstOrFail();

        // ② 库里存的不是明文
        $this->assertNotSame('secret123', $user->password);

        // ③ 用 Hash::check 能校验通过
        $this->assertTrue(Hash::check('secret123', $user->password));
    }
}
