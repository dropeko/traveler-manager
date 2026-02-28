<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        $token = auth('api')->login($user);

        return $this->respondWithToken($token, $user, 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        return $this->respondWithToken($token, $user);
    }

    private function respondWithToken(string $token, User $user, int $status = 200): JsonResponse
    {
        $ttlMinutes = (int) config('jwt.ttl', 60);

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'expires_in' => $ttlMinutes * 60,
            'user' => new UserResource($user),
        ], $status);
    }
}