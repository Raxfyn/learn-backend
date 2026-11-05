<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\UserController;
/*
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
*/

//公开：注册 + 登陆
Route::post('/users', [UserController::class, 'store']);
Route::post('/login', [UserController::class, 'login'])->name('login');

//Route::post('/login', [UserController::class, 'login']);
//受保护：列表、我的资料、登出
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/users', [UserController::class, 'index']);

    Route::get('/profile', function (Request $request) {
        return response()->json($request->user(), 200, [], JSON_UNESCAPED_UNICODE);
    });

    //单端登出：只让当前这个token失效
    Route::post('/logout', function (Request $request) {

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => '已退出'], 200, [], JSON_UNESCAPED_UNICODE);
    });

    Route::patch('/users/{user}', [UserController::class, 'update']);

    Route::delete('/users/{user}', [UserController::class, 'destroy']);
});

// 加一个假登录路由，只为让 auth 中间件不报错
//Route::post('/login', [UserController::class, 'login'])->name('login');
