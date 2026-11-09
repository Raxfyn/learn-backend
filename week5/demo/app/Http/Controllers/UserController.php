<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\StoreUserRequest;
use App\Jobs\SendWelcomeEmail;
use App\Events\UserRegistered;
use App\Http\ApiResponse;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\UserResource;


class UserController extends Controller
{
    public function index(Request $request)
    {
        return ApiResponse::paginated(
            User::paginate((int) $request->query("per_page", 15)),
            UserResource::class
        );
        //return UserResource::collection(User::all());
        //return response()->json(User::all(), 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function store(StoreUserRequest $request)
    {
        //$validated = $request->validate(['name' => 'required|string|max:50']);
        $user = User::create($request->validated());

        UserRegistered::dispatch($user); //或者event(new UserRegistered($user));

        //SendWelcomeEmail::dispatch($user);  //只写一行jobs记录，立刻返回

        return ApiResponse::success(new UserResource($user), '注册成功', 201);
        //return response()->json($user, 201, [], JSON_UNESCAPED_UNICODE);
        //return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        // 三种失败统一成一句话：用户不存在 / 老用户没密码 / 密码不对
        if (! $user || empty($user->password) || ! Hash::check($data['password'], $user->password)) {
            return ApiResponse::error('凭据不正确', 401);
            //return response()->json(['message' => '凭据不正确'], 401);
        }

        //$token = $user->createToken('api')->plainTextToken;

        return ApiResponse::success([
            'token'      => $user->createToken('api')->plainTextToken,
            'token_type' => 'Bearer',
        ], '登录成功');
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());

        return ApiResponse::success(new UserResource($user), '更新成功');
        //return new UserResource($user);
        //return response()->json($user, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function destroy(Request $request, User $user)
    {
        Gate::authorize('delete', $user);      // 拒绝时抛 AuthorizationException → 403

        $user->tokens()->delete();             // 先撤销 token，别留孤儿
        $user->posts()->get()->each->delete();
        $user->delete();

        return ApiResponse::success(null, '已删除');
        //return response()->json(null, 204);
    }

    // UserController
    public function profile(Request $request)
    {
        return ApiResponse::success(new UserResource($request->user()));
        //return new UserResource($request->user());
    }
}
