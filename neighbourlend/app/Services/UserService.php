<?php

namespace App\Services;

use App\Domain\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Domain\States\ReservationState;
use Illuminate\Support\Facades\DB;
use Exception;

class UserService
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function createUser(array $data): ?User
    {
        return $this->userRepository->create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'phone'    => $data['phone'] ?? null,
            'address'  => $data['address'] ?? null,
            'picture'  => $data['picture'] ?? null,
        ]);
    }

    public function getUserById(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    public function updateUser(User $user, array $data): User
    {
        // Discard null or empty password so it isn't overwritten accidentally
        if (array_key_exists('password', $data)) {
            if (empty($data['password'])) {
                unset($data['password']);
            }
        }

        // Fill dirty attributes into the in-memory domain model
        $user->fill($data);

        $saved = $this->userRepository->save($user);

        if (!$saved) {
            throw new Exception('Failed to update user profile.');
        }

        return $user;
    }

    public function deleteUser(User $user)
    {
        DB::transaction(function () use ($user) {
            // Domain Rule: Prevent account deletion if the user has active tool reservations
            $hasActiveReservations = $user->reservations()
                ->whereIn('status', [
                    ReservationState::REQUESTED->value,
                    ReservationState::APPROVED->value,
                    ReservationState::ACTIVE->value,
                ])
                ->exists();

            if ($hasActiveReservations) {
                throw new Exception('Cannot delete account while you have active or pending reservations.');
            }

            // Revoke all personal access tokens issued via Sanctum
            $user->tokens()->delete();

            // Persist the deletion
            $deleted = $this->userRepository->delete($user);

            if (!$deleted) {
                throw new Exception('Failed to delete user account.');
            }
        });
    }
}
