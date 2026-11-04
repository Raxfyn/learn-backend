<?php

namespace App\Jobs;

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;  //总共尝试3次
    public int $backoff = 2; //两次之间间隔2秒

    public function __construct(public User $user) {}

    public function handle(): void
    {
        //throw new \RuntimeException('boom: 模拟第三方邮件服务挂了');

        if (empty($this->user->email)) {
            Log::info('跳过欢迎邮件：用户没有邮箱', ['user_id' => $this->user->id]);
            return;
        }

        Mail::to($this->user->email)->send(new WelcomeMail($this->user));

        Log::info('欢迎邮件已发送', ['user_id' => $this->user->id, 'to' => $this->user->email]);
    }
}
