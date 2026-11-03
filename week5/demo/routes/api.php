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

/*
Route::get('/users', function () {
    $users = DB::table('users')->get();
    return response()->json($users, 200, [], JSON_UNESCAPED_UNICODE);
});
*/

Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);

//Route::post('/login', [UserController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', function (Request $request) {
        return response()->json($request->user(), 200, [], JSON_UNESCAPED_UNICODE);
    });
});

// 加一个假登录路由，只为让 auth 中间件不报错
Route::post('/login', [UserController::class, 'login'])->name('login');
