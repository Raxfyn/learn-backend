<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\StoreUserRequest;
use App\Jobs\SendWelcomeEmail;
use App\Events\UserRegistered;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Support\Facades\Gate;

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

    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        // 三种失败统一成一句话：用户不存在 / 老用户没密码 / 密码不对
        if (! $user || empty($user->password) || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => '凭据不正确'], 401, [], JSON_UNESCAPED_UNICODE);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token'      => $token,
            'token_type' => 'Bearer',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    /*
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
*/
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());

        return response()->json($user, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function destroy(Request $request, User $user)
    {
        Gate::authorize('delete', $user);      // 拒绝时抛 AuthorizationException → 403

        $user->tokens()->delete();             // 先撤销 token，别留孤儿
        $user->delete();

        return response()->json(null, 204);
    }
}
