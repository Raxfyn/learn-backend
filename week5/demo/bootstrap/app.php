<?php

use App\Http\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(\App\Http\Middleware\LogApiRequest::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // 401：未认证 / token 失效
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return $request->is('api/*')
                ? ApiResponse::error('未认证或 token 已失效', 401)
                : null;
        });

        // 422：表单验证失败
        $exceptions->render(function (ValidationException $e, Request $request) {
            return $request->is('api/*')
                ? ApiResponse::error('参数校验失败', 422, $e->errors())
                : null;
        });

        // 403 / 404 / 405 / 429 等 HttpException 家族
        $exceptions->render(function (HttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $e->getStatusCode();

            $messages = [
                403 => '无权限执行该操作',
                404 => '资源不存在',
                405 => '请求方法不被允许',
                429 => '请求过于频繁，请稍后再试',
            ];

            return ApiResponse::error($messages[$status] ?? ($e->getMessage() ?: '请求失败'), $status);
        });
    })
    ->create();
