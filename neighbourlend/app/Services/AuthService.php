<?php

namespace App\Services;

use App\Domain\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    // AuthService implementation
    public function __construct(
        private UserService $userService,
        private UserRepositoryInterface $userRepository
    ) {}

    public function register(array $data): array
    {
        // 1. Delegate core user creation
        $user = $this->userService->createUser($data);

        // 2. Auth-specific concern: create authentication token
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    /**
     * Authenticate user credentials and return an API token.
     */
    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !Hash::check($password, $user->password)) {
            // Throwing ValidationException allows Laravel controllers to
            // automatically return standard 422 JSON errors to Vue
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        // Generate Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    /**
     * Revoke the token currently being used by the authenticated user.
     */
    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }
}
