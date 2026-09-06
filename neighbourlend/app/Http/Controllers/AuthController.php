<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'User registered successfully.',
            'user'    => $result['user'],
            'token'   => $result['token'],
        ], Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->input('email'),
            $request->input('password')
        );

        return response()->json([
            'message' => 'Login successful.',
            'user'    => $result['user'],
            'token'   => $result['token'],
        ], Response::HTTP_OK);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Logged out successfully.',
        ], Response::HTTP_OK);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user(), Response::HTTP_OK);
    }
}
