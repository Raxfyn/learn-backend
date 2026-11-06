<?php

namespace App\Http\Controllers;

use App\Http\ApiResponse;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\User;


class PostController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status'   => ['sometimes', 'in:draft,published'],
            'q'        => ['sometimes', 'string', 'max:50'],
            'sort'     => ['sometimes', 'string', 'max:20'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Post::query()->with('user');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['q'])) {
            $query->where('title', 'like', '%' . $filters['q'] . '%');
        }

        // 排序必须白名单：绝不能把用户输入直接塞进 orderBy
        $sortable  = ['id', 'created_at', 'title'];
        $sort      = $filters['sort'] ?? '-id';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column    = ltrim($sort, '-');

        if (! in_array($column, $sortable, true)) {
            abort(422, 'sort 字段不合法');
        }

        $posts = $query->orderBy($column, $direction)
            ->paginate($filters['per_page'] ?? 15);

        return ApiResponse::paginated($posts, PostResource::class);
    }


    public function store(StorePostRequest $request)
    {
        $post = $request->user()->posts()->create($request->validated());

        return ApiResponse::success(new PostResource($post), '创建成功', 201);
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        $post->update($request->validated());

        return ApiResponse::success(new PostResource($post), '更新成功');
    }

    public function destroy(Request $request, Post $post)
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return ApiResponse::success(null, '已删除');
    }

    public function indexForUser(User $user, Request $request)
    {
        $posts = $user->posts()->with('user')->latest('id')
            ->paginate((int) $request->query('per_page', 15));

        return ApiResponse::paginated($posts, PostResource::class);
    }
}
