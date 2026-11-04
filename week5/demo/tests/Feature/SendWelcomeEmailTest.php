<?php

namespace Tests\Feature;

use App\Jobs\SendWelcomeEmail;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendWelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_sends_welcome_mail(): void
    {
        Mail::fake();

        $user = User::create(['name' => '王五', 'email' => 'wangwu@test.com']);

        (new SendWelcomeEmail($user))->handle();      // 跳过队列，直接执行

        Mail::assertSent(WelcomeMail::class, function (WelcomeMail $mail) use ($user) {
            return $mail->hasTo($user->email)
                && $mail->user->email === $user->email;
        });

        Mail::assertSentCount(1);
    }

    public function test_job_skips_user_without_email(): void
    {
        Mail::fake();

        $user = User::create(['name' => '无邮箱']);

        (new SendWelcomeEmail($user))->handle();

        Mail::assertNothingSent();                    // 守卫生效：一封都不许发
    }

    public function test_welcome_mail_renders_user_name(): void
    {
        $user = User::create(['name' => '赵六', 'email' => 'zhaoliu@test.com']);

        $html = (new WelcomeMail($user))->render();

        $this->assertStringContainsString('赵六', $html);
    }
}
