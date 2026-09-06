<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function show(UserRequest $request): JsonResponse
    {
        return response()->json($request->user(), Response::HTTP_OK);
    }

    public function update(UserRequest $request): JsonResponse
    {
        $updatedUser = $this->userService->updateUser(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user'    => $updatedUser,
        ], Response::HTTP_OK);
    }

    public function delete(UserRequest $request): JsonResponse
    {
        $this->userService->deleteUser($request->user());

        return response()->json([
            'message' => 'Account deleted successfully.',
        ], Response::HTTP_NO_CONTENT);
    }
}
