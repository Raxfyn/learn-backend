<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\StoreUserRequest;

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
