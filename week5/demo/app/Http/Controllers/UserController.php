<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\StoreUserRequest;
use App\Jobs\SendWelcomeEmail;
use App\Events\UserRegistered;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::all(), 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function store(StoreUserRequest $request)
    {
        //$validated = $request->validate(['name' => 'required|string|max:50']);
        $user = User::create($request->validated());

        UserRegistered::dispatch($user); //或者event(new UserRegistered($user));

        //SendWelcomeEmail::dispatch($user);  //只写一行jobs记录，立刻返回

        return response()->json($user, 201, [], JSON_UNESCAPED_UNICODE);
    }

    public function login(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:50']);

        // 先查，没有再创建
        $user = User::where('name', $validated['name'])->first();
        if (!$user) {
            $user = User::create(['name' => $validated['name']]);
        }

        $token = $user->createToken('api')->plainTextToken;
        return response()->json(['token' => $token, 'user_id' => $user->id], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
