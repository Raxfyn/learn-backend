<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

/*
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
*/


Route::get('/users', function () {
    $users = DB::table('users')->get();
    return response()->json($users, 200, [], JSON_UNESCAPED_UNICODE);
});
