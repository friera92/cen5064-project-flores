<?php

namespace App\Repositories;

use App\Domain\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    // Implement the methods defined in the UserRepositoryInterface

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function save(User $user): bool
    {
        return $user->save();
    }

    public function delete(int $id): bool
    {
        $user = User::find($id);
        if ($user) {
            return $user->delete();
        }
        return false;
    }
}
