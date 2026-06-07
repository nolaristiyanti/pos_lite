<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when(
                $request->search,
                fn ($query) =>
                $query->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
            )
            ->latest()
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => $request->role,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user,
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function update(
        UpdateUserRequest $request,
        User $user
    ): JsonResponse {
    
        if (
            auth()->id() === $user->id &&
            $request->role !== 'admin'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot change your own role.',
            ], 422);
        }
    
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ]);
    
        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user->fresh(),
        ]);
    }
}