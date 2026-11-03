<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::all(), 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:50']);
        $user = User::create($validated);
        return response()->json($user, 201, [], JSON_UNESCAPED_UNICODE);
    }
}
